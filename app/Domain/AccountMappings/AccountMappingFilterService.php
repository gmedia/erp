<?php

namespace App\Domain\AccountMappings;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AccountMappingFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['source_coa_version_id'])) {
            $query->whereHas('sourceAccount', function ($q) use ($filters) {
                $q->where('coa_version_id', $filters['source_coa_version_id']);
            });
        }

        if (! empty($filters['target_coa_version_id'])) {
            $query->whereHas('targetAccount', function ($q) use ($filters) {
                $q->where('coa_version_id', $filters['target_coa_version_id']);
            });
        }
    }

    public function applySearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('notes', 'like', "%{$search}%")
                ->orWhereHas('sourceAccount', function ($sq) use ($search) {
                    $sq->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                })
                ->orWhereHas('targetAccount', function ($tq) use ($search) {
                    $tq->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
        });
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
