<?php

namespace App\Domain\ArReceipts;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class ArReceiptFilterService
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
                'bank_account_id' => 'bank_account_id',
                'status' => 'status',
                'payment_method' => 'payment_method',
                'currency' => 'currency',
            ],
            [
                'receipt_date' => ['from' => 'receipt_date_from', 'to' => 'receipt_date_to'],
            ],
            [
                'total_amount' => ['min' => 'total_amount_min', 'max' => 'total_amount_max'],
            ],
        );
    }
}
