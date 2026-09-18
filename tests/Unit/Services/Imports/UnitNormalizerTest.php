<?php

namespace Tests\Unit\Services\Imports;

use App\Enums\ProductUnitType;
use App\Services\Imports\UnitNormalizer;
use InvalidArgumentException;
use Tests\TestCase;

class UnitNormalizerTest extends TestCase
{
    private UnitNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new UnitNormalizer();
    }

    public function test_it_resolves_liter_unit(): void
    {
        $r = $this->normalizer->normalize('1 لتر');

        $this->assertSame(ProductUnitType::Liter->value, $r['unit_type']);
        $this->assertSame(1.0, (float) $r['unit_value']);
        $this->assertSame('1 لتر', $r['label']);
    }

    public function test_it_expands_european_thousands_dot(): void
    {
        $r = $this->normalizer->normalize('باكيت 100.000 بذرة');

        $this->assertSame(ProductUnitType::Packet->value, $r['unit_type']);
        $this->assertSame(100000.0, (float) $r['unit_value']);
    }

    public function test_it_handles_sack_with_trailing_whitespace(): void
    {
        $r = $this->normalizer->normalize('شوال (25كغم )');

        $this->assertSame(ProductUnitType::Sack->value, $r['unit_type']);
        $this->assertSame(25.0, (float) $r['unit_value']);
    }

    public function test_it_resolves_half_kilogram_packet(): void
    {
        $r = $this->normalizer->normalize('باكيت (1/2 كغم)');

        $this->assertSame(ProductUnitType::Kilogram->value, $r['unit_type']);
        $this->assertSame(0.5, (float) $r['unit_value']);
    }

    public function test_it_resolves_tube_as_grams(): void
    {
        $r = $this->normalizer->normalize('انبوب 750 غم');

        $this->assertSame(ProductUnitType::Gram->value, $r['unit_type']);
        $this->assertSame(750.0, (float) $r['unit_value']);
    }

    public function test_it_trims_surrounding_whitespace(): void
    {
        $r = $this->normalizer->normalize('   1 كغم   ');

        $this->assertSame(ProductUnitType::Kilogram->value, $r['unit_type']);
        $this->assertSame(1.0, (float) $r['unit_value']);
    }

    public function test_it_preserves_internal_whitespace_as_lookup_key(): void
    {
        // 'باكيت 100 بذرة' and 'باكيت 100بذرة' are distinct source variants;
        // both must resolve identically.
        $a = $this->normalizer->normalize('باكيت 100 بذرة');
        $b = $this->normalizer->normalize('باكيت 100بذرة');

        $this->assertSame($a['unit_type'], $b['unit_type']);
        $this->assertSame($a['unit_value'], $b['unit_value']);
        $this->assertSame($a['label'], $b['label']);
    }

    public function test_it_throws_on_unknown_unit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unmapped product unit/');

        $this->normalizer->normalize('برميل 200 لتر');
    }

    public function test_has_returns_true_for_known_unit(): void
    {
        $this->assertTrue($this->normalizer->has('طن'));
        $this->assertTrue($this->normalizer->has('  طن  '));
    }

    public function test_has_returns_false_for_unknown_unit(): void
    {
        $this->assertFalse($this->normalizer->has('برميل'));
    }

    public function test_every_mapping_entry_has_valid_unit_type(): void
    {
        $validTypes = array_map(fn ($c) => $c->value, ProductUnitType::cases());

        foreach (config('product_units') as $raw => $entry) {
            $this->assertArrayHasKey('unit_type', $entry, "Entry for [$raw] missing unit_type");
            $this->assertArrayHasKey('unit_value', $entry, "Entry for [$raw] missing unit_value");
            $this->assertArrayHasKey('label', $entry, "Entry for [$raw] missing label");
            $this->assertContains($entry['unit_type'], $validTypes, "Entry for [$raw] has invalid unit_type");
            $this->assertGreaterThan(0, (float) $entry['unit_value'], "Entry for [$raw] has non-positive unit_value");
        }
    }
}
