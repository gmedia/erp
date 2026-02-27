<?php

namespace App\Domain\Pipelines;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class PipelineFilterService
{
    use BaseFilterService;

    /**
     * @param Builder<\App\Models\Pipeline> $query
     * @param array<string, mixed> $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (isset($filters['entity_type']) && $filters['entity_type'] !== '') {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }
    }
}
