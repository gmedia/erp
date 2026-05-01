<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

/**
 * Export class for warehouses.
 */
class WarehouseExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Warehouse::query()->with(['branch']);

        $this->applySearchFilter($query, $this->filters, ['code', 'name']);
        $this->applyExactFilters($query, $this->filters, ['branch_id' => 'branch_id']);

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->normalizeSortDirection($this->filters);
        $allowedSortColumns = ['code', 'name', 'branch_id', 'created_at', 'updated_at'];

        if ($sortBy === 'branch') {
            $query
                ->leftJoin('branches', 'warehouses.branch_id', '=', 'branches.id')
                ->select('warehouses.*')
                ->orderBy('branches.name', $sortDirection);
        } elseif (in_array($sortBy, $allowedSortColumns, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($warehouse): array
    {
        return $this->mapExportRow($warehouse, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (Warehouse $warehouse): mixed => $warehouse->id,
            'Code' => fn (Warehouse $warehouse): mixed => $warehouse->code,
            'Name' => fn (Warehouse $warehouse): mixed => $warehouse->name,
            'Branch' => fn (Warehouse $warehouse): mixed => $this->relatedAttribute($warehouse, 'branch', 'name'),
            'Created At' => fn (Warehouse $warehouse): mixed => $this->formatIso8601($warehouse->created_at),
        ];
    }
}
