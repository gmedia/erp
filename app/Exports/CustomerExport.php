<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class CustomerExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Customer::query()->with(['branch', 'category']);

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

    public function map($customer): array
    {
        return $this->mapExportRow($customer, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => static fn (Customer $customer): mixed => $customer->id,
            'Name' => static fn (Customer $customer): mixed => $customer->name,
            'Email' => static fn (Customer $customer): mixed => $customer->email,
            'Phone' => static fn (Customer $customer): mixed => $customer->phone,
            'Address' => static fn (Customer $customer): mixed => $customer->address,
            'Branch' => static fn (Customer $customer): mixed => $customer->branch?->name,
            'Category' => static fn (Customer $customer): mixed => $customer->category?->name,
            'Status' => static fn (Customer $customer): mixed => ucfirst($customer->status),
            'Notes' => static fn (Customer $customer): mixed => $customer->notes,
            'Created At' => static fn (Customer $customer): mixed => $customer->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
