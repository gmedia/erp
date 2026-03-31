<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class SupplierExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Supplier::query()->with(['branch']);

        $this->applySearchFilter($query, $this->filters, ['name', 'email', 'phone']);
        $this->applyExactFilters($query, $this->filters, [
            'branch_id' => 'branch_id',
            'category_id' => 'category_id',
            'status' => 'status',
        ]);
        $this->applySorting($query, $this->filters, ['name', 'email', 'phone', 'branch_id', 'category_id', 'status', 'created_at']);

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Address',
            'Branch',
            'Category',
            'Status',
            'Created At',
        ];
    }

    public function map($supplier): array
    {
        return [
            $supplier->id,
            $supplier->name,
            $supplier->email,
            $supplier->phone,
            $supplier->address,
            $supplier->branch?->name,
            $supplier->category?->name,
            ucfirst($supplier->status),
            $supplier->created_at?->toIso8601String(),
        ];
    }
}
