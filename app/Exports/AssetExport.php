<?php

namespace App\Exports;

use App\Domain\Assets\AssetFilterService;
use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class AssetExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Asset::query()->with([
            'category',
            'model',
            'branch',
            'location',
            'department',
            'employee',
            'supplier',
        ]);

        $filterService = app(AssetFilterService::class);

        if (! empty($this->filters['search'])) {
            $filterService->applySearch(
                $query,
                $this->filters['search'],
                ['name', 'asset_code', 'serial_number', 'barcode'],
            );
        } else {
            $filterService->applyAdvancedFilters($query, [
                'asset_category_id' => $this->filters['asset_category_id'] ?? null,
                'asset_model_id' => $this->filters['asset_model_id'] ?? null,
                'branch_id' => $this->filters['branch_id'] ?? null,
                'asset_location_id' => $this->filters['asset_location_id'] ?? null,
                'department_id' => $this->filters['department_id'] ?? null,
                'employee_id' => $this->filters['employee_id'] ?? null,
                'status' => $this->filters['status'] ?? null,
                'condition' => $this->filters['condition'] ?? null,
            ]);
        }

        $filterService->applySorting(
            $query,
            $this->filters['sort_by'] ?? 'created_at',
            $this->normalizeSortDirection($this->filters),
            [
                'id',
                'asset_code',
                'name',
                'purchase_date',
                'purchase_cost',
                'status',
                'created_at',
                'category',
                'branch',
            ],
        );

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($asset): array
    {
        return $this->mapExportRow($asset, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (Asset $a): mixed => $a->id,
            'Asset Code' => fn (Asset $a): mixed => $a->asset_code,
            'Name' => fn (Asset $a): mixed => $a->name,
            'Category' => fn (Asset $a): mixed => $this->relatedAttribute($a, 'category', 'name'),
            'Model' => fn (Asset $a): mixed => $a->model?->model_name,
            'Serial Number' => fn (Asset $a): mixed => $a->serial_number,
            'Barcode' => fn (Asset $a): mixed => $a->barcode,
            'Branch' => fn (Asset $a): mixed => $this->relatedAttribute($a, 'branch', 'name'),
            'Location' => fn (Asset $a): mixed => $this->relatedAttribute($a, 'location', 'name'),
            'Department' => fn (Asset $a): mixed => $this->relatedAttribute($a, 'department', 'name'),
            'Employee' => fn (Asset $a): mixed => $this->relatedAttribute($a, 'employee', 'name'),
            'Supplier' => fn (Asset $a): mixed => $this->relatedAttribute($a, 'supplier', 'name'),
            'Purchase Date' => fn (Asset $a): mixed => $this->formatDateValue($a->purchase_date, 'Y-m-d'),
            'Purchase Cost' => fn (Asset $a): mixed => $a->purchase_cost,
            'Currency' => fn (Asset $a): mixed => $a->currency,
            'Warranty End Date' => fn (Asset $a): mixed => $this->formatDateValue($a->warranty_end_date, 'Y-m-d'),
            'Status' => fn (Asset $a): mixed => $a->status,
            'Condition' => fn (Asset $a): mixed => $a->condition,
            'Depreciation Method' => fn (Asset $a): mixed => $a->depreciation_method,
            'Useful Life (Months)' => fn (Asset $a): mixed => $a->useful_life_months,
            'Created At' => fn (Asset $a): mixed => $this->formatIso8601($a->created_at),
        ];
    }
}
