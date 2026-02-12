<?php

namespace App\Exports;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Asset::query()->with(['category', 'model', 'branch', 'location', 'department', 'employee', 'supplier']);

        $filterService = app(\App\Domain\Assets\AssetFilterService::class);

        if (!empty($this->filters['search'])) {
            $filterService->applySearch($query, $this->filters['search'], ['name', 'asset_code', 'serial_number', 'barcode']);
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
            $this->filters['sort_direction'] ?? 'desc',
            ['id', 'asset_code', 'name', 'purchase_date', 'purchase_cost', 'status', 'created_at', 'category', 'branch']
        );

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID', 'Asset Code', 'Name', 'Category', 'Model', 'Serial Number', 'Barcode',
            'Branch', 'Location', 'Department', 'Employee', 'Supplier',
            'Purchase Date', 'Purchase Cost', 'Currency', 'Warranty End Date',
            'Status', 'Condition', 'Depreciation Method', 'Useful Life (Months)', 'Created At'
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->id,
            $asset->asset_code,
            $asset->name,
            $asset->category?->name,
            $asset->model?->model_name,
            $asset->serial_number,
            $asset->barcode,
            $asset->branch?->name,
            $asset->location?->name,
            $asset->department?->name,
            $asset->employee?->name,
            $asset->supplier?->name,
            $asset->purchase_date?->format('Y-m-d'),
            $asset->purchase_cost,
            $asset->currency,
            $asset->warranty_end_date?->format('Y-m-d'),
            $asset->status,
            $asset->condition,
            $asset->depreciation_method,
            $asset->useful_life_months,
            $asset->created_at?->toIso8601String(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
