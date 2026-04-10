<?php

namespace App\Domain\AccountMappings;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AccountMappingFilterService
{
    use BaseFilterService {
        applySearch as protected applyBaseSearch;
    }

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'type' => 'type',
        ]);

        $this->applyRelatedExactFilters($query, $filters, [
            'source_coa_version_id' => ['relation' => 'sourceAccount', 'column' => 'coa_version_id'],
            'target_coa_version_id' => ['relation' => 'targetAccount', 'column' => 'coa_version_id'],
        ]);
    }

    public function applySearch(Builder $query, string $search): void
    {
        $this->applyBaseSearch($query, $search, ['notes'], [
            'sourceAccount' => ['code', 'name'],
            'targetAccount' => ['code', 'name'],
        ]);
    }

    public function applySorting(Builder $query, string $sortBy, string $sortDirection): void
    {
        $allowedSorts = ['id', 'type', 'source', 'target', 'notes', 'created_at', 'updated_at'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy('created_at', 'desc');

            return;
        }

        $this->applySortingWithRelationFallback(
            $query,
            $sortBy,
            $sortDirection,
            $allowedSorts,
            [
                'source' => $this->relationSortConfig('accounts', 'account_mappings.source_account_id', 'code', tableAlias: 'source_acc'),
                'target' => $this->relationSortConfig('accounts', 'account_mappings.target_account_id', 'code', tableAlias: 'target_acc'),
            ],
            'account_mappings',
        );
    }
}
