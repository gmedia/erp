<?php

namespace App\Domain\JournalEntries;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class JournalEntryFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'status' => 'status',
            ],
            [
                'entry_date' => ['from' => 'start_date', 'to' => 'end_date'],
            ],
        );
    }
}
