<?php

namespace App\Domain\SupplierBills;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class SupplierBillFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'supplier_id' => 'supplier_id',
                'branch_id' => 'branch_id',
                'fiscal_year_id' => 'fiscal_year_id',
                'status' => 'status',
                'currency' => 'currency',
            ],
            [
                'bill_date' => ['from' => 'bill_date_from', 'to' => 'bill_date_to'],
                'due_date' => ['from' => 'due_date_from', 'to' => 'due_date_to'],
            ],
            [
                'grand_total' => ['min' => 'grand_total_min', 'max' => 'grand_total_max'],
                'amount_due' => ['min' => 'amount_due_min', 'max' => 'amount_due_max'],
            ],
        );
    }
}
