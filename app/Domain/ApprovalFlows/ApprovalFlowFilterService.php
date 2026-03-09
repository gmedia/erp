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
            $query->where('approvable_type', $filters['approvable_type']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', $filters['is_active']);
        }
    }
}
