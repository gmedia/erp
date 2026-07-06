<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

class SupplierExport extends BaseExport
{
    public function __construct(protected readonly array $filters = []) {}

    public function query(): Builder
    {
        $query = Supplier::query()->with(['branch', 'category']);

        $this->applyConfiguredFilters($query, $this->filters, ['name', 'email', 'phone'], [
            'branch_id' => 'branch_id',
            'category_id' => 'category_id',
            'status' => 'status',
        ], [], ['name', 'email', 'phone', 'branch_id', 'category_id', 'status', 'created_at']);

        return $query;
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
