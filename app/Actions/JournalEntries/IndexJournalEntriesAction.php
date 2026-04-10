<?php

namespace App\Actions\JournalEntries;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\JournalEntries\JournalEntryFilterService;
use App\Http\Requests\JournalEntries\IndexJournalEntryRequest;
use App\Models\JournalEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexJournalEntriesAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private JournalEntryFilterService $filterService
    ) {}

    public function execute(IndexJournalEntryRequest $request): LengthAwarePaginator
    {
        $query = JournalEntry::query()
            ->with(['lines.account', 'fiscalYear', 'createdBy', 'postedBy'])
            ->withSum('lines as total_debit', 'debit');

        return $this->handleIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['entry_number', 'description', 'reference'],
            ['start_date', 'end_date', 'status'],
            'entry_date',
            ['entry_date', 'entry_number', 'description', 'reference', 'total_debit', 'status', 'created_at'],
        );
    }
}
