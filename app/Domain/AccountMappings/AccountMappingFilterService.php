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
}
