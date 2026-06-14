<?php

namespace App\Services\Currency;

use App\Exceptions\Currency\MixedCurrencyException;
use Illuminate\Database\Query\Builder;

class CurrencyGuard
{
    /**
     * Throw MixedCurrencyException if the given query returns rows
     * with more than one distinct currency value.
     */
    public function assertHomogeneousQuery(
        Builder $query,
        string $context,
        string $column = 'currency',
    ): void {
        $currencies = $query
            ->clone()
            ->distinct()
            ->pluck($column)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (count($currencies) > 1) {
            throw new MixedCurrencyException($context, array_map('strval', $currencies));
        }
    }

    /**
     * Throw MixedCurrencyException if the given iterable contains
     * more than one distinct currency value.
     *
     * @param  iterable<int, array<string, mixed>|object>  $rows
     */
    public function assertHomogeneousRows(
        iterable $rows,
        string $context,
        string $column = 'currency',
    ): void {
        $seen = [];

        foreach ($rows as $row) {
            $value = is_array($row) ? ($row[$column] ?? null) : ($row->{$column} ?? null);

            if ($value === null || $value === '') {
                continue;
            }

            $seen[(string) $value] = true;

            if (count($seen) > 1) {
                throw new MixedCurrencyException($context, array_keys($seen));
            }
        }
    }
}
