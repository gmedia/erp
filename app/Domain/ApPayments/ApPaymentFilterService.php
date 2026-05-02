<?php

namespace App\Domain\ApPayments;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class ApPaymentFilterService
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
                'bank_account_id' => 'bank_account_id',
                'status' => 'status',
                'payment_method' => 'payment_method',
                'currency' => 'currency',
            ],
            [
                'payment_date' => ['from' => 'payment_date_from', 'to' => 'payment_date_to'],
            ],
            [
                'total_amount' => ['min' => 'total_amount_min', 'max' => 'total_amount_max'],
            ],
        );
    }
}
