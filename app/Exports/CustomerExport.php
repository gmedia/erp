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

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Customer::query()->with(['branch', 'category']);

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
            'Notes',
            'Created At',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->name,
            $customer->email,
            $customer->phone,
            $customer->address,
            $customer->branch?->name,
            $customer->category?->name,
            ucfirst($customer->status),
            $customer->notes,
            $customer->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
