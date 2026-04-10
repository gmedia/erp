<?php

namespace App\Domain\ApprovalFlows;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class ApprovalFlowFilterService
{
    use BaseFilterService;

    /**
     * @param  Builder<\App\Models\ApprovalFlow>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (isset($filters['approvable_type']) && $filters['approvable_type'] !== '') {
            $this->applyExactFilters($query, $filters, [
                'approvable_type' => 'approvable_type',
            ]);
        }

        $this->applyBooleanFilter($query, $filters, 'is_active');
    }
}
