<?php

namespace App\Domain\CustomerInvoices;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class CustomerInvoiceFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'customer_id' => 'customer_id',
                'branch_id' => 'branch_id',
                'fiscal_year_id' => 'fiscal_year_id',
                'status' => 'status',
                'currency' => 'currency',
            ],
            [
                'invoice_date' => ['from' => 'invoice_date_from', 'to' => 'invoice_date_to'],
                'due_date' => ['from' => 'due_date_from', 'to' => 'due_date_to'],
            ],
            [
                'grand_total' => ['min' => 'grand_total_min', 'max' => 'grand_total_max'],
                'amount_due' => ['min' => 'amount_due_min', 'max' => 'amount_due_max'],
            ],
        );
    }
}
