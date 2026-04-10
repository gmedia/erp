<?php

namespace App\Domain\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait AppliesPartyExactFilters
{
    /**
     * @param  Builder<\App\Models\Customer|\App\Models\Supplier>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyPartyExactFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'branch_id' => 'branch_id',
            'category_id' => 'category_id',
            'status' => 'status',
        ]);
    }
}
