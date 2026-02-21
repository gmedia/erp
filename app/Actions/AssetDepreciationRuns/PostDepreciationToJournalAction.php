<?php

namespace App\Actions\AssetDepreciationRuns;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\AssetDepreciationRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostDepreciationToJournalAction
{
    public function __construct(
        private CreateJournalEntryAction $createJournalEntryAction
    ) {}

    public function execute(AssetDepreciationRun $run): void
    {
        if ($run->status !== 'calculated') {
            throw ValidationException::withMessages([
                'status' => 'Only calculated runs can be posted.'
            ]);
        }

        DB::transaction(function () use ($run) {
            $run->load('lines.asset');

            if ($run->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'lines' => 'Cannot post an empty depreciation run.'
                ]);
            }

            $journalLines = [];
            $summary = [];

            // Group by expense and accumulated accounts
            foreach ($run->lines as $line) {
                $asset = $line->asset;
                $expenseAccount = $asset->depreciation_expense_account_id;
                $accumulatedAccount = $asset->accumulated_depr_account_id;

                if (!$expenseAccount || !$accumulatedAccount) {
                    throw ValidationException::withMessages([
                        'accounts' => "Asset {$asset->asset_code} is missing depreciation accounts."
                    ]);
                }

                if (!isset($summary[$expenseAccount])) {
                    $summary[$expenseAccount] = ['debit' => 0, 'credit' => 0];
                }
                $summary[$expenseAccount]['debit'] += $line->amount;

                if (!isset($summary[$accumulatedAccount])) {
                    $summary[$accumulatedAccount] = ['debit' => 0, 'credit' => 0];
                }
                $summary[$accumulatedAccount]['credit'] += $line->amount;

                // Update asset cache
                $asset->update([
                    'accumulated_depreciation' => $line->accumulated_after,
                    'book_value' => $line->book_value_after,
                ]);
            }

            foreach ($summary as $accountId => $amounts) {
                if ($amounts['debit'] > 0) {
                    $journalLines[] = [
                        'account_id' => $accountId,
                        'debit' => $amounts['debit'],
                        'credit' => 0,
                        'memo' => 'Depreciation Expense',
                    ];
                }
                if ($amounts['credit'] > 0) {
                    $journalLines[] = [
                        'account_id' => $accountId,
                        'debit' => 0,
                        'credit' => $amounts['credit'],
                        'memo' => 'Accumulated Depreciation',
                    ];
                }
            }

            $journalEntryData = [
                'entry_date' => $run->period_end->format('Y-m-d'),
                'reference' => 'DEPR-' . $run->period_start->format('Ym'),
                'description' => 'Depreciation Run for ' . $run->period_start->format('M Y'),
                'lines' => $journalLines,
            ];

            $journalEntry = $this->createJournalEntryAction->execute($journalEntryData);

            $run->update([
                'status' => 'posted',
                'journal_entry_id' => $journalEntry->id,
                'posted_by' => \Illuminate\Support\Facades\Auth::id(),
                'posted_at' => now(),
            ]);
        });
    }
}
