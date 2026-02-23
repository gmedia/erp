<?php

namespace App\Actions\JournalEntries;

use App\Exports\JournalEntryExport;
use App\Http\Requests\JournalEntries\IndexJournalEntryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportJournalEntriesAction
{
    public function execute(IndexJournalEntryRequest $request): JsonResponse
    {
        $filters = $request->validated();
        
        $filename = 'journal_entries_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new JournalEntryExport($filters), $filePath, 'public');

        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
