<?php

namespace App\Actions\JournalEntries;

use App\Actions\Concerns\AssertsJournalLineAccounts;
use App\Actions\PeriodClosings\AssertPeriodOpenForPostingAction;
use App\Models\JournalEntry;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostJournalAction
{
    use AssertsJournalLineAccounts;

    /**
     * @param  array<int>  $ids
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
                if (! $entry->isBalanced()) {
                    throw new Exception("Journal entry {$entry->entry_number} is not balanced.");
                }

                DB::transaction(function () use ($entry) {
                    app(AssertPeriodOpenForPostingAction::class)
                        ->execute($entry->fiscal_year_id, $entry->entry_date->format('Y-m-d'));

                    $this->assertLinesUseActiveAccounts(
                        $entry->lines->map(fn ($line): array => ['account_id' => $line->account_id])->all(),
                        $entry->entry_date->format('Y-m-d'),
                    );

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
