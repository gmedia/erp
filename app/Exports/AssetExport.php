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

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['branch'])) {
            $query->where('branch_id', $this->filters['branch']);
        }
        if (!empty($this->filters['category'])) {
            $query->where('asset_category_id', $this->filters['category']);
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $allowedSortColumns = ['asset_code', 'name', 'purchase_date', 'status', 'created_at'];
        
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }

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
