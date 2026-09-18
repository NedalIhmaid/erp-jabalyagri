<?php

namespace App\Services\Imports;

use InvalidArgumentException;

class UnitNormalizer
{
    /** @var array<string, array{unit_type:string, unit_value:float, label:string}> */
    private array $map;

    public function __construct(?array $map = null)
    {
        $this->map = $map ?? config('product_units');
    }

    /**
     * Normalize a raw unit string from the source catalogue.
     *
     * @return array{unit_type:string, unit_value:float, label:string}
     */
    public function normalize(string $raw): array
    {
        $key = trim($raw);

        if (! isset($this->map[$key])) {
            throw new InvalidArgumentException(sprintf(
                'Unmapped product unit: %s. Add it to config/product_units.php before re-running the import.',
                $key
            ));
        }

        return $this->map[$key];
    }

    public function has(string $raw): bool
    {
        return isset($this->map[trim($raw)]);
    }
}
