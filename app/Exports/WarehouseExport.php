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

    protected $filters;

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
        return ['ID', 'Code', 'Name', 'Branch', 'Created At'];
    }

    public function map($warehouse): array
    {
        return [
            $warehouse->id,
            $warehouse->code,
            $warehouse->name,
            $warehouse->branch?->name,
            $warehouse->created_at?->toIso8601String(),
        ];
    }
}
