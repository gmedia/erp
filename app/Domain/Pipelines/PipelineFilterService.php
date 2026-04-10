<?php

namespace App\Domain\Pipelines;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class PipelineFilterService
{
    use BaseFilterService;

    /**
     * @param  Builder<\App\Models\Pipeline>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (isset($filters['entity_type']) && $filters['entity_type'] !== '') {
            $this->applyExactFilters($query, $filters, [
                'entity_type' => 'entity_type',
            ]);
        }

        $this->applyResolvedBooleanFilter(
            $query,
            $filters,
            'is_active',
            static fn (mixed $value): bool => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        );
    }
}
