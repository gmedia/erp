<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomerExport extends BaseExport
{
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

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (Customer $customer): mixed => $customer->id,
            'Name' => fn (Customer $customer): mixed => $customer->name,
            'Email' => fn (Customer $customer): mixed => $customer->email,
            'Phone' => fn (Customer $customer): mixed => $customer->phone,
            'Address' => fn (Customer $customer): mixed => $customer->address,
            'Branch' => fn (Customer $customer): mixed => $this->relatedAttribute($customer, 'branch', 'name'),
            'Category' => fn (Customer $customer): mixed => $this->relatedAttribute($customer, 'category', 'name'),
            'Status' => fn (Customer $customer): mixed => ucfirst($customer->status),
            'Notes' => fn (Customer $customer): mixed => $customer->notes,
            'Created At' => fn (Customer $customer): mixed => $this->formatDateValue(
                $customer->created_at,
                'Y-m-d H:i:s',
            ),
        ];
    }
}
