<?php

namespace App\Domain\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Base filter service trait providing common search and sorting functionality.
 *
 * This trait provides reusable query filtering methods for Eloquent models.
 * Use in domain filter services to reduce code duplication.
 */
trait BaseFilterService
{
    public function normalizeSortDirection(string $sortDirection): string
    {
        return strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
    }

    /**
     * Apply search filters to query across multiple fields and optionally relationships.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<int, string>  $searchFields
     * @param  array<string, array<int, string>>  $relationSearchFields
     */
    public function applySearch(
        Builder $query,
        string $search,
        array $searchFields,
        array $relationSearchFields = []
    ): void {
        $query->where(function ($q) use ($search, $searchFields, $relationSearchFields) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }

            foreach ($relationSearchFields as $relation => $fields) {
                $q->orWhereHas($relation, function ($relationQuery) use ($search, $fields) {
                    $relationQuery->where(function ($rq) use ($search, $fields) {
                        foreach ($fields as $field) {
                            $rq->orWhere($field, 'like', "%{$search}%");
                        }
                    });
                });
            }
        });
    }

    /**
     * Apply search with alias fields that target relation columns.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<int, string>  $searchFields
     * @param  array<string, array{relation: string, column: string}>  $relationFieldAliases
     */
    public function applySearchWithRelationAliases(
        Builder $query,
        string $search,
        array $searchFields,
        array $relationFieldAliases
    ): void {
        $query->where(function ($q) use ($search, $searchFields, $relationFieldAliases) {
            foreach ($searchFields as $field) {
                if (isset($relationFieldAliases[$field])) {
                    $alias = $relationFieldAliases[$field];
                    $q->orWhereHas($alias['relation'], function ($relationQuery) use ($search, $alias) {
                        $relationQuery->where($alias['column'], 'like', "%{$search}%");
                    });

                    continue;
                }

                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Apply standard asset alias search fields used by asset-related modules.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<int, string>  $searchFields
     */
    public function applyAssetAliasSearch(Builder $query, string $search, array $searchFields): void
    {
        $this->applySearchWithRelationAliases($query, $search, $searchFields, [
            'asset_name' => ['relation' => 'asset', 'column' => 'name'],
            'asset_code' => ['relation' => 'asset', 'column' => 'asset_code'],
        ]);
    }

    /**
     * Apply mapped relation sorting using join metadata.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<string, array{table: string, local_column: string, foreign_column: string, order_column: string, join?: 'join'|'leftJoin'}>  $relationSortMap
     */
    public function applyMappedRelationSorting(
        Builder $query,
        string $sortBy,
        string $sortDirection,
        array $relationSortMap,
        string $baseTable
    ): bool {
        if (! isset($relationSortMap[$sortBy])) {
            return false;
        }

        $config = $relationSortMap[$sortBy];
        $joinMethod = $config['join'] ?? 'join';

        $query->{$joinMethod}($config['table'], $config['local_column'], '=', $config['foreign_column'])
            ->orderBy($config['order_column'], $sortDirection)
            ->select($baseTable . '.*');

        return true;
    }

    /**
     * Apply sorting to query with validation against allowed columns.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<int, string>  $allowedSorts
     */
    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $this->normalizeSortDirection($sortDirection));
        }
    }

    /**
     * Apply sorting with relation map fallback while preserving base-table ordering behavior.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<int, string>  $allowedSorts
     * @param  array<string, array{table: string, local_column: string, foreign_column: string, order_column: string, join?: 'join'|'leftJoin'}>  $relationSortMap
     */
    public function applySortingWithRelationFallback(
        Builder $query,
        string $sortBy,
        string $sortDirection,
        array $allowedSorts,
        array $relationSortMap,
        string $baseTable
    ): void {
        if (! in_array($sortBy, $allowedSorts, true)) {
            return;
        }

        $sortDirection = $this->normalizeSortDirection($sortDirection);

        if (! $this->applyMappedRelationSorting($query, $sortBy, $sortDirection, $relationSortMap, $baseTable)) {
            $query->orderBy($sortBy, $sortDirection);
        }
    }

    /**
     * Apply exact-match filters where request key maps to a database column.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $fieldMap  Request key => column name
     */
    public function applyExactFilters(Builder $query, array $filters, array $fieldMap): void
    {
        foreach ($fieldMap as $filterKey => $column) {
            if (! empty($filters[$filterKey])) {
                $query->where($column, $filters[$filterKey]);
            }
        }
    }

    /**
     * Apply date range filters with optional from/to keys.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @param  array<string, array{from: string, to: string}>  $dateRanges  Column => keys
     */
    public function applyDateRanges(Builder $query, array $filters, array $dateRanges): void
    {
        foreach ($dateRanges as $column => $rangeKeys) {
            if (! empty($filters[$rangeKeys['from']])) {
                $query->whereDate($column, '>=', $filters[$rangeKeys['from']]);
            }

            if (! empty($filters[$rangeKeys['to']])) {
                $query->whereDate($column, '<=', $filters[$rangeKeys['to']]);
            }
        }
    }

    /**
     * Apply numeric min/max filters where request key maps to a database column.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @param  array<string, array{min: string, max: string}>  $numericRanges  Column => keys
     */
    public function applyNumericRanges(Builder $query, array $filters, array $numericRanges): void
    {
        foreach ($numericRanges as $column => $rangeKeys) {
            if (! empty($filters[$rangeKeys['min']])) {
                $query->where($column, '>=', $filters[$rangeKeys['min']]);
            }

            if (! empty($filters[$rangeKeys['max']])) {
                $query->where($column, '<=', $filters[$rangeKeys['max']]);
            }
        }
    }

    /**
     * Apply grouped exact, date-range, and numeric-range filters from a single configuration payload.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $exactFilters
     * @param  array<string, array{from: string, to: string}>  $dateRanges
     * @param  array<string, array{min: string, max: string}>  $numericRanges
     */
    public function applyConfiguredFilters(
        Builder $query,
        array $filters,
        array $exactFilters = [],
        array $dateRanges = [],
        array $numericRanges = []
    ): void {
        if ($exactFilters !== []) {
            $this->applyExactFilters($query, $filters, $exactFilters);
        }

        if ($dateRanges !== []) {
            $this->applyDateRanges($query, $filters, $dateRanges);
        }

        if ($numericRanges !== []) {
            $this->applyNumericRanges($query, $filters, $numericRanges);
        }
    }

    /**
     * Build a normalized relation sort config entry.
     *
     * @return array{table: string, local_column: string, foreign_column: string, order_column: string, join?: 'join'|'leftJoin'}
     */
    public function relationSortConfig(
        string $table,
        string $localColumn,
        string $orderColumn = 'name',
        string $join = 'join'
    ): array {
        $config = [
            'table' => $table,
            'local_column' => $localColumn,
            'foreign_column' => $table . '.id',
            'order_column' => $table . '.' . $orderColumn,
        ];

        if ($join !== 'join') {
            $config['join'] = $join;
        }

        return $config;
    }
}
