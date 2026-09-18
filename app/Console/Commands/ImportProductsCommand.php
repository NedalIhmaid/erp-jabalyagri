<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Services\Imports\UnitNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportProductsCommand extends Command
{
    protected $signature = 'products:import
                            {path : Absolute path to the xlsx workbook}
                            {--dry-run : Parse and validate without writing to the database}
                            {--skip-existing : Skip product_unit rows that already exist for a product+label pair}';

    protected $description = 'Import the company product catalogue from an xlsx workbook (name + raw unit columns).';

    public function handle(UnitNormalizer $unitNormalizer): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("Workbook not found: {$path}");

            return self::FAILURE;
        }

        $this->info("Reading {$path}…");

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $rows = $reader->load($path)->getActiveSheet()->toArray(null, true, true, true);

        // Validate every unit first; fail fast before touching the DB.
        $parsed = [];
        $errors = [];

        foreach ($rows as $rowNum => $row) {
            if ($rowNum === 1) {
                continue; // header
            }

            $name = $this->normalizeProductName((string) ($row['A'] ?? ''));
            $rawUnit = trim((string) ($row['B'] ?? ''));

            if ($name === '' && $rawUnit === '') {
                continue;
            }

            if ($name === '' || $rawUnit === '') {
                $errors[] = "Row {$rowNum}: missing name or unit";
                continue;
            }

            if (! $unitNormalizer->has($rawUnit)) {
                $errors[] = "Row {$rowNum}: unmapped unit [{$rawUnit}] for product [{$name}]";
                continue;
            }

            $unit = $unitNormalizer->normalize($rawUnit);

            $parsed[] = [
                'name' => $name,
                'unit' => $unit,
            ];
        }

        if (! empty($errors)) {
            $this->error('Aborting — '.count($errors).' row(s) failed validation:');
            foreach ($errors as $err) {
                $this->line("  • {$err}");
            }

            return self::FAILURE;
        }

        $this->info(sprintf('Validated %d rows. %d distinct products.',
            count($parsed),
            count(array_unique(array_column($parsed, 'name')))
        ));

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no database writes.');

            return self::SUCCESS;
        }

        $summary = DB::transaction(function () use ($parsed) {
            $productsTouched = 0;
            $unitsCreated = 0;
            $unitsSkipped = 0;

            $grouped = [];
            foreach ($parsed as $row) {
                $grouped[$row['name']]['units'][] = $row['unit'];
            }

            foreach ($grouped as $name => $data) {
                $product = Product::updateOrCreate(
                    ['sku' => $this->skuFor($name)],
                    [
                        'name'      => $name,
                        'is_active' => true,
                    ]
                );
                $productsTouched++;

                foreach ($data['units'] as $unit) {
                    $existing = ProductUnit::where('product_id', $product->id)
                        ->where('label', $unit['label'])
                        ->first();

                    if ($existing) {
                        if ($this->option('skip-existing')) {
                            $unitsSkipped++;
                            continue;
                        }

                        $existing->update([
                            'unit_type'  => $unit['unit_type'],
                            'unit_value' => $unit['unit_value'],
                            // price + needs_price_review are NOT touched on update;
                            // finance may have already priced this row.
                        ]);
                        $unitsSkipped++;
                        continue;
                    }

                    ProductUnit::create([
                        'product_id'         => $product->id,
                        'label'              => $unit['label'],
                        'unit_type'          => $unit['unit_type'],
                        'unit_value'         => $unit['unit_value'],
                        'price'              => 0,
                        'needs_price_review' => true,
                        'is_active'          => true,
                    ]);
                    $unitsCreated++;
                }
            }

            return compact('productsTouched', 'unitsCreated', 'unitsSkipped');
        });

        $this->info('Import complete:');
        $this->line("  Products upserted:  {$summary['productsTouched']}");
        $this->line("  Unit rows created:  {$summary['unitsCreated']}");
        $this->line("  Unit rows skipped:  {$summary['unitsSkipped']}");
        $this->warn('All new unit rows are flagged needs_price_review=true with price=0 — finance must fill before sales requests can reference them.');

        return self::SUCCESS;
    }

    private function skuFor(string $name): string
    {
        return 'PROD-'.substr(sha1($name), 0, 10);
    }

    private function normalizeProductName(string $name): string
    {
        // Keep names verbatim from the workbook — only trim and collapse whitespace.
        return trim(preg_replace('/\s+/u', ' ', trim($name)) ?? $name);
    }
}
