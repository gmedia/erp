<?php

namespace App\Domain\PurchaseRequests;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRequestFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'branch_id' => 'branch_id',
            'department_id' => 'department_id',
            'requested_by' => 'requested_by',
            'priority' => 'priority',
            'status' => 'status',
        ]);

        $this->applyDateRanges($query, $filters, [
            'request_date' => ['from' => 'request_date_from', 'to' => 'request_date_to'],
            'required_date' => ['from' => 'required_date_from', 'to' => 'required_date_to'],
        ]);

        $this->applyNumericRanges($query, $filters, [
            'estimated_amount' => ['min' => 'estimated_amount_min', 'max' => 'estimated_amount_max'],
        ]);
    }
}
