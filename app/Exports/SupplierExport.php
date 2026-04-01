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

        $this->applyConfiguredFilters($query, $this->filters, ['name', 'email', 'phone'], [
            'branch_id' => 'branch_id',
            'category_id' => 'category_id',
            'status' => 'status',
        ], [], ['name', 'email', 'phone', 'branch_id', 'category_id', 'status', 'created_at']);

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($supplier): array
    {
        return $this->mapExportRow($supplier, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (Supplier $supplier): mixed => $supplier->id,
            'Name' => fn (Supplier $supplier): mixed => $supplier->name,
            'Email' => fn (Supplier $supplier): mixed => $supplier->email,
            'Phone' => fn (Supplier $supplier): mixed => $supplier->phone,
            'Address' => fn (Supplier $supplier): mixed => $supplier->address,
            'Branch' => fn (Supplier $supplier): mixed => $this->relatedAttribute($supplier, 'branch', 'name'),
            'Category' => fn (Supplier $supplier): mixed => $this->relatedAttribute($supplier, 'category', 'name'),
            'Status' => fn (Supplier $supplier): mixed => ucfirst($supplier->status),
            'Created At' => fn (Supplier $supplier): mixed => $this->formatIso8601($supplier->created_at),
        ];
    }
}
