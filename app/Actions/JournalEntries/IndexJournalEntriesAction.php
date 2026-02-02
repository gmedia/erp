<?php

namespace App\Actions\JournalEntries;

use App\Domain\JournalEntries\JournalEntryFilterService;
use App\Http\Requests\JournalEntries\IndexJournalEntryRequest;
use App\Models\JournalEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexJournalEntriesAction
{
    public function __construct(
        private JournalEntryFilterService $filterService
    ) {}

    public function execute(IndexJournalEntryRequest $request): LengthAwarePaginator
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $query = JournalEntry::query()
            ->with(['lines.account', 'fiscalYear', 'createdBy', 'postedBy']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), [
                'entry_number', 'description', 'reference'
            ]);
        }

        $this->filterService->applyAdvancedFilters($query, [
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'status' => $request->get('status'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'entry_date'),
            $request->get('sort_direction', 'desc'),
            ['entry_date', 'entry_number', 'created_at']
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
