<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Product::query()->with(['category', 'unit', 'branch']);

        // Apply search filter
        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply category filter
        if (! empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        // Apply unit filter
        if (! empty($this->filters['unit_id'])) {
            $query->where('unit_id', $this->filters['unit_id']);
        }

        // Apply branch filter
        if (! empty($this->filters['branch_id'])) {
            $query->where('branch_id', $this->filters['branch_id']);
        }

        // Apply type filter
        if (! empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        // Apply status filter
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
 
        // Apply billing model filter
        if (! empty($this->filters['billing_model'])) {
            $query->where('billing_model', $this->filters['billing_model']);
        }

        // Apply sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';

        $allowedSortColumns = ['code', 'name', 'type', 'cost', 'selling_price', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Name',
            'Type',
            'Category',
            'Unit',
            'Cost',
            'Selling Price',
            'Status',
            'Created At',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->code,
            $product->name,
            $product->type,
            $product->category?->name,
            $product->unit?->name,
            $product->cost,
            $product->selling_price,
            $product->status,
            $product->created_at?->toIso8601String(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
