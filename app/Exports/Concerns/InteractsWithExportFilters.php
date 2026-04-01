<?php

namespace App\Exports\Concerns;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait InteractsWithExportFilters
{
    /**
     * @return array<int, array<string, array<string, bool>>>
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $columns
     */
    protected function applySearchFilter(Builder $query, array $filters, array $columns): void
    {
        if (empty($filters['search'])) {
            return;
        }

        $search = (string) $filters['search'];
        $query->where(function (Builder $builder) use ($search, $columns): void {
            foreach ($columns as $column) {
                $builder->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $searchColumns
     * @param  array<string, string>  $exactFilters
     * @param  array<string, array{from: string, to: string}>  $dateRangeFilters
     * @param  array<int, string>  $allowedSortColumns
     */
    protected function applyConfiguredFilters(
        Builder $query,
        array $filters,
        array $searchColumns,
        array $exactFilters = [],
        array $dateRangeFilters = [],
        array $allowedSortColumns = []
    ): void {
        $this->applySearchFilter($query, $filters, $searchColumns);
        $this->applyExactFilters($query, $filters, $exactFilters);
        $this->applyDateRangeFilters($query, $filters, $dateRangeFilters);
        $this->applySorting($query, $filters, $allowedSortColumns);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $map
     */
    protected function applyExactFilters(Builder $query, array $filters, array $map): void
    {
        foreach ($map as $filterKey => $column) {
            if (! empty($filters[$filterKey])) {
                $query->where($column, $filters[$filterKey]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $map
     */
    protected function applyPresentFilters(Builder $query, array $filters, array $map): void
    {
        foreach ($map as $filterKey => $column) {
            if (array_key_exists($filterKey, $filters) && $filters[$filterKey] !== '') {
                $query->where($column, $filters[$filterKey]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, array{from: string, to: string}>  $map
     */
    protected function applyDateRangeFilters(Builder $query, array $filters, array $map): void
    {
        foreach ($map as $column => $rangeKeys) {
            if (! empty($filters[$rangeKeys['from']])) {
                $query->whereDate($column, '>=', $filters[$rangeKeys['from']]);
            }

            if (! empty($filters[$rangeKeys['to']])) {
                $query->whereDate($column, '<=', $filters[$rangeKeys['to']]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $allowedSortColumns
     */
    protected function applySorting(Builder $query, array $filters, array $allowedSortColumns): void
    {
        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDirection = $this->normalizeSortDirection($filters);

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function normalizeSortDirection(array $filters): string
    {
        return strtolower((string) ($filters['sort_direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
    }

    /**
     * @param  array<string, callable(mixed): mixed>  $columns
     * @return array<int, string>
     */
    protected function exportHeadings(array $columns): array
    {
        return array_keys($columns);
    }

    /**
     * @param  array<string, callable(mixed): mixed>  $columns
     * @return array<int, mixed>
     */
    protected function mapExportRow(mixed $row, array $columns): array
    {
        return array_values(array_map(
            static fn (callable $resolver): mixed => $resolver($row),
            $columns,
        ));
    }

    protected function relatedAttribute(Model $model, string $relation, string $attribute): mixed
    {
        $related = $model->getRelationValue($relation);

        if (! $related instanceof Model) {
            return null;
        }

        return $related->getAttribute($attribute);
    }

    protected function formatDateValue(mixed $value, string $format): ?string
    {
        if (! $value instanceof DateTimeInterface) {
            return null;
        }

        return $value->format($format);
    }

    protected function formatIso8601(mixed $value): ?string
    {
        return $this->formatDateValue($value, DateTimeInterface::ATOM);
    }
}
