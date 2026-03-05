<?php

namespace App\Domain\PurchaseRequests;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRequestFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['requested_by'])) {
            $query->where('requested_by', $filters['requested_by']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['request_date_from'])) {
            $query->whereDate('request_date', '>=', $filters['request_date_from']);
        }

        if (! empty($filters['request_date_to'])) {
            $query->whereDate('request_date', '<=', $filters['request_date_to']);
        }

        if (! empty($filters['required_date_from'])) {
            $query->whereDate('required_date', '>=', $filters['required_date_from']);
        }

        if (! empty($filters['required_date_to'])) {
            $query->whereDate('required_date', '<=', $filters['required_date_to']);
        }

        if (! empty($filters['estimated_amount_min'])) {
            $query->where('estimated_amount', '>=', $filters['estimated_amount_min']);
        }

        if (! empty($filters['estimated_amount_max'])) {
            $query->where('estimated_amount', '<=', $filters['estimated_amount_max']);
        }
    }
}
