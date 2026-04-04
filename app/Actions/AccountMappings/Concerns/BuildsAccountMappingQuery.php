<?php

namespace App\Actions\AccountMappings\Concerns;

use App\Domain\AccountMappings\AccountMappingFilterService;
use App\Models\AccountMapping;
use Illuminate\Database\Eloquent\Builder;

trait BuildsAccountMappingQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    protected function buildAccountMappingQuery(
        AccountMappingFilterService $filterService,
        array $filters,
        ?string $search,
        string $sortBy,
        string $sortDirection,
    ): Builder {
        $query = AccountMapping::query()->with([
            'sourceAccount.coaVersion',
            'targetAccount.coaVersion',
        ]);

        if ($search !== null && $search !== '') {
            $filterService->applySearch($query, $search);
        }

        $filterService->applyAdvancedFilters($query, $filters);
        $filterService->applySorting($query, $sortBy, $sortDirection);

        return $query;
    }
}
