<?php

namespace App\Http\Controllers;

use App\Actions\RecurringJournals\ExecuteRecurringJournalAction;
use App\Actions\RecurringJournals\ExportRecurringJournalsAction;
use App\Actions\RecurringJournals\IndexRecurringJournalsAction;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\RecurringJournals\ExportRecurringJournalRequest;
use App\Http\Requests\RecurringJournals\IndexRecurringJournalRequest;
use App\Http\Requests\RecurringJournals\StoreRecurringJournalRequest;
use App\Http\Requests\RecurringJournals\UpdateRecurringJournalRequest;
use App\Http\Resources\JournalEntries\JournalEntryResource;
use App\Http\Resources\RecurringJournals\RecurringJournalCollection;
use App\Http\Resources\RecurringJournals\RecurringJournalResource;
use App\Models\RecurringJournal;
use Illuminate\Http\JsonResponse;

class RecurringJournalController extends Controller
{
    use StoresItemsInTransaction;

    public function index(IndexRecurringJournalRequest $request, IndexRecurringJournalsAction $action): JsonResponse
    {
        return (new RecurringJournalCollection($action->execute($request)))->response();
    }

    public function store(StoreRecurringJournalRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lines = $data['lines'];
        unset($data['lines']);
        $data['created_by'] = auth()->id();
        $data['total_amount'] = collect($lines)->sum(fn (array $line): float => (float) $line['debit']);

        $journal = $this->storeWithSyncedItems(
            $data,
            $lines,
            fn (array $attributes): RecurringJournal => RecurringJournal::create($attributes),
            fn (RecurringJournal $journal): null => null,
            fn (RecurringJournal $journal, array $items): null => $this->syncLines($journal, $items),
        );

        return (new RecurringJournalResource($journal->load(['fiscalYear', 'creator', 'lines.account'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(RecurringJournal $recurringJournal): JsonResponse
    {
        return (new RecurringJournalResource($recurringJournal->load(['fiscalYear', 'creator', 'lines.account'])))->response();
    }

    public function update(UpdateRecurringJournalRequest $request, RecurringJournal $recurringJournal): JsonResponse
    {
        $data = $request->validated();
        $lines = $data['lines'];
        unset($data['lines']);
        $data['total_amount'] = collect($lines)->sum(fn (array $line): float => (float) $line['debit']);

        $this->updateWithSyncedItems(
            $recurringJournal,
            $data,
            $lines,
            fn (array $attributes): array => $attributes,
            fn (RecurringJournal $journal, array $items): null => $this->syncLines($journal, $items),
        );

        return (new RecurringJournalResource($recurringJournal->refresh()->load(['fiscalYear', 'creator', 'lines.account'])))->response();
    }

    public function destroy(RecurringJournal $recurringJournal): JsonResponse
    {
        $recurringJournal->lines()->delete();
        $recurringJournal->delete();

        return response()->json(null, 204);
    }

    public function export(ExportRecurringJournalRequest $request, ExportRecurringJournalsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    public function execute(RecurringJournal $recurringJournal, ExecuteRecurringJournalAction $action): JsonResponse
    {
        return (new JournalEntryResource($action->execute($recurringJournal)))->response()->setStatusCode(201);
    }

    private function syncLines(RecurringJournal $journal, array $items): null
    {
        $journal->lines()->delete();

        foreach ($items as $item) {
            $journal->lines()->create($item);
        }

        return null;
    }
}
