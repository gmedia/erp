<?php

namespace App\Http\Controllers;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Actions\JournalEntries\IndexJournalEntriesAction;
use App\Actions\JournalEntries\UpdateJournalEntryAction;
use App\Http\Requests\JournalEntries\IndexJournalEntryRequest;
use App\Http\Requests\JournalEntries\StoreJournalEntryRequest;
use App\Http\Requests\JournalEntries\UpdateJournalEntryRequest;
use App\Actions\JournalEntries\ExportJournalEntriesAction;
use App\Http\Resources\JournalEntries\JournalEntryCollection;
use App\Http\Resources\JournalEntries\JournalEntryResource;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;

class JournalEntryController extends Controller
{
    public function index(IndexJournalEntryRequest $request, IndexJournalEntriesAction $action): JsonResponse
    {
        $journalEntries = $action->execute($request);

        return (new JournalEntryCollection($journalEntries))->response();
    }

    public function store(StoreJournalEntryRequest $request, CreateJournalEntryAction $action): JsonResponse
    {
        $journalEntry = $action->execute($request->validated());

        return (new JournalEntryResource($journalEntry))
            ->response()
            ->setStatusCode(201);
    }

    public function show(JournalEntry $journalEntry): JsonResponse
    {
        $journalEntry->load(['lines.account', 'fiscalYear', 'createdBy', 'postedBy']);
        return (new JournalEntryResource($journalEntry))->response();
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry, UpdateJournalEntryAction $action): JsonResponse
    {
        $journalEntry = $action->execute($journalEntry, $request->validated());

        return (new JournalEntryResource($journalEntry))->response();
    }

    public function export(IndexJournalEntryRequest $request, ExportJournalEntriesAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    public function destroy(JournalEntry $journalEntry): JsonResponse
    {
        if ($journalEntry->status === 'posted') {
             return response()->json(['message' => 'Cannot delete posted journal entry.'], 403);
        }

        $journalEntry->lines()->delete();
        $journalEntry->delete();

        return response()->json(null, 204);
    }
}
