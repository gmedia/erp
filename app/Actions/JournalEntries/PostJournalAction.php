<?php

namespace App\Actions\JournalEntries;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class PostJournalAction
{
    /**
     * @param array<int> $ids
     * @return array{success: int, failures: array<int, string>}
     */
    public function execute(array $ids): array
    {
        $results = [
            'success' => 0,
            'failures' => [],
        ];

        $entries = JournalEntry::whereIn('id', $ids)
            ->where('status', 'draft')
            ->with('lines')
            ->get();

        foreach ($entries as $entry) {
            try {
                if (!$entry->isBalanced()) {
                    throw new Exception("Journal entry {$entry->entry_number} is not balanced.");
                }

                DB::transaction(function () use ($entry) {
                    $entry->update([
                        'status' => 'posted',
                        'posted_at' => now(),
                        'posted_by' => Auth::id(),
                    ]);
                });

                $results['success']++;
            } catch (Exception $e) {
                $results['failures'][$entry->id] = $e->getMessage();
            }
        }

        return $results;
    }
}
