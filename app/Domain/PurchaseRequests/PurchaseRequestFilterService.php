<?php

namespace App\Domain\PurchaseRequests;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRequestFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'branch_id' => 'branch_id',
                'department_id' => 'department_id',
                'requested_by' => 'requested_by',
                'priority' => 'priority',
                'status' => 'status',
            ],
            [
                'request_date' => ['from' => 'request_date_from', 'to' => 'request_date_to'],
                'required_date' => ['from' => 'required_date_from', 'to' => 'required_date_to'],
            ],
            [
                'estimated_amount' => ['min' => 'estimated_amount_min', 'max' => 'estimated_amount_max'],
            ],
        );
    }
}
