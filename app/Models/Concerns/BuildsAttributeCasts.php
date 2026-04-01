<?php

namespace App\Models\Concerns;

trait BuildsAttributeCasts
{
    protected function booleanCasts(array $attributes): array
    {
        return $this->castsWithType($attributes, 'boolean');
    }

    protected function dateCasts(array $attributes): array
    {
        return $this->castsWithType($attributes, 'date');
    }

    protected function datetimeCasts(array $attributes): array
    {
        return $this->castsWithType($attributes, 'datetime');
    }

    protected function decimalCasts(array $attributes, int $precision = 2): array
    {
        return $this->castsWithType($attributes, 'decimal:' . $precision);
    }

    protected function integerCasts(array $attributes): array
    {
        return $this->castsWithType($attributes, 'integer');
    }

    private function castsWithType(array $attributes, string $type): array
    {
        return array_fill_keys($attributes, $type);
    }
}
