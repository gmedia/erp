<?php

namespace App\Actions\RecurringJournals;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\RecurringJournals\RecurringJournalFilterService;
use App\Http\Requests\RecurringJournals\IndexRecurringJournalRequest;
use App\Models\RecurringJournal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexRecurringJournalsAction
{
    use InteractsWithIndexRequest;

    public function __construct(private RecurringJournalFilterService $filterService) {}

    public function execute(IndexRecurringJournalRequest $request): LengthAwarePaginator
    {
        $query = RecurringJournal::query()->with(['fiscalYear', 'creator', 'lines.account']);

        return $this->handleIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['name', 'description'],
            ['frequency', 'is_active', 'fiscal_year_id', 'next_run_from', 'next_run_to'],
            'next_run_date',
            ['name', 'frequency', 'next_run_date', 'last_run_date', 'total_amount', 'is_active', 'created_at'],
        );
    }
}
