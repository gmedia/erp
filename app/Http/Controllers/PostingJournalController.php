<?php

namespace App\Http\Controllers;

use App\Actions\JournalEntries\IndexJournalEntriesAction;
use App\Actions\JournalEntries\PostJournalAction;
use App\Http\Requests\JournalEntries\IndexJournalEntryRequest;
use App\Http\Requests\JournalEntries\PostJournalRequest;
use App\Http\Resources\JournalEntries\JournalEntryCollection;
use Illuminate\Http\JsonResponse;

class PostingJournalController extends Controller
{
    public function index(IndexJournalEntryRequest $request, IndexJournalEntriesAction $action): JsonResponse
    {
        // Force draft status and balanced condition if possible, 
        // though index action might not support balanced filter directly yet.
        // We'll filter balanced manually in the collection if needed or rely on frontend.
        // According to IndexJournalEntriesAction, it accepts status filter.
        // Force draft status
        $request->query->set('status', 'draft');
        $request->offsetSet('status', 'draft');
        
        $journalEntries = $action->execute($request);

        return (new JournalEntryCollection($journalEntries))->response();
    }

    public function post(PostJournalRequest $request, PostJournalAction $action): JsonResponse
    {
        $results = $action->execute($request->validated('ids'));

        return response()->json([
            'message' => "Successfully posted {$results['success']} journal entries.",
            'success_count' => $results['success'],
            'failures' => $results['failures'],
        ]);
    }
}
