<?php

namespace App\Actions\ApprovalDelegations\Concerns;

use App\Domain\ApprovalDelegations\ApprovalDelegationFilterService;
use App\Models\ApprovalDelegation;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithApprovalDelegationQuery
{
    protected function buildFilteredQuery(
        ApprovalDelegationFilterService $filterService,
        array $filters,
        array $sortableFields
    ): Builder {
        $query = ApprovalDelegation::query()
            ->with(['delegator:id,name', 'delegate:id,name']);

        if (! empty($filters['search'])) {
            $filterService->applySearch(
                $query,
                $filters['search'],
                ['reason'],
                [
                    'delegator' => ['name'],
                    'delegate' => ['name'],
                ]
            );
        }

        $filterService->applyAdvancedFilters($query, $filters);
        $filterService->applySorting(
            $query,
            $filters['sort_by'] ?? 'created_at',
            $filters['sort_direction'] ?? 'desc',
            $sortableFields,
        );

        return $query;
    }
}
