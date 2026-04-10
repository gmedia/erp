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
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'source') {
            $query->join('accounts as source_acc', 'account_mappings.source_account_id', '=', 'source_acc.id')
                ->orderBy('source_acc.code', $sortDirection)
                ->select('account_mappings.*');
        } elseif ($sortBy === 'target') {
            $query->join('accounts as target_acc', 'account_mappings.target_account_id', '=', 'target_acc.id')
                ->orderBy('target_acc.code', $sortDirection)
                ->select('account_mappings.*');
        } elseif (in_array($sortBy, ['id', 'type', 'notes', 'created_at', 'updated_at'], true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }
}
