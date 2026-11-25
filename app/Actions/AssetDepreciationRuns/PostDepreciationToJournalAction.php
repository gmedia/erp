<?php

namespace App\Actions\AssetDepreciationRuns;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Asset;
use App\Models\AssetDepreciationRun;
use Illuminate\Support\Facades\Auth;
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
                'status' => 'Only calculated runs can be posted.',
            ]);
        }

        DB::transaction(function () use ($run) {
            $run->load('lines.asset');

            if ($run->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'lines' => 'Cannot post an empty depreciation run.',
                ]);
            }

            $journalLines = [];
            $summary = [];

            foreach ($run->lines as $line) {
                // Kunci aset agar akumulasi tidak berubah antara cek dan update.
                $asset = Asset::whereKey($line->asset_id)->lockForUpdate()->firstOrFail();

                // Aset yang sudah dilepas/hilang tidak boleh disusutkan lagi (#442).
                if (! in_array($asset->status, Asset::IN_SERVICE_STATUSES, true)) {
                    throw ValidationException::withMessages([
                        'lines' => __('asset_depreciation_runs.asset_not_in_service', [
                            'code' => $asset->asset_code,
                            'status' => $asset->status,
                        ]),
                    ]);
                }

                $calculatedAccumulated = round((float) $line->accumulated_before, 2);
                $currentAccumulated = round((float) $asset->accumulated_depreciation, 2);

                if ($calculatedAccumulated !== $currentAccumulated) {
                    throw ValidationException::withMessages([
                        'lines' => __('asset_depreciation_runs.asset_changed_after_calculated', [
                            'code' => $asset->asset_code,
                        ]),
                    ]);
                }

                $expenseAccount = $asset->depreciation_expense_account_id;
                $accumulatedAccount = $asset->accumulated_depr_account_id;

                if (! $expenseAccount || ! $accumulatedAccount) {
                    throw ValidationException::withMessages([
                        'accounts' => "Asset {$asset->asset_code} is missing depreciation accounts.",
                    ]);
                }

                $branchId = $asset->branch_id;
                $expenseKey = $expenseAccount . '-'
                    . $branchId;
                $accumulatedKey = $accumulatedAccount . '-'
                    . $branchId;

                if (! isset($summary[$expenseKey])) {
                    $summary[$expenseKey] = [
                        'account_id' => $expenseAccount,
                        'branch_id' => $branchId,
                        'debit' => 0,
                        'credit' => 0,
                    ];
                }
                $summary[$expenseKey]['debit'] += $line->amount;

                if (! isset($summary[$accumulatedKey])) {
                    $summary[$accumulatedKey] = [
                        'account_id' => $accumulatedAccount,
                        'branch_id' => $branchId,
                        'debit' => 0,
                        'credit' => 0,
                    ];
                }
                $summary[$accumulatedKey]['credit'] += $line->amount;

                $asset->update([
                    'accumulated_depreciation' => $line->accumulated_after,
                    'book_value' => $line->book_value_after,
                ]);
            }

            foreach ($summary as $amounts) {
                if ($amounts['debit'] > 0) {
                    $journalLines[] = [
                        'account_id' => $amounts['account_id'],
                        'branch_id' => $amounts['branch_id'],
                        'debit' => $amounts['debit'],
                        'credit' => 0,
                        'memo' => __('asset_depreciation_run.memo_expense'),
                    ];
                }
                if ($amounts['credit'] > 0) {
                    $journalLines[] = [
                        'account_id' => $amounts['account_id'],
                        'branch_id' => $amounts['branch_id'],
                        'debit' => 0,
                        'credit' => $amounts['credit'],
                        'memo' => __('asset_depreciation_run.memo_accumulated'),
                    ];
                }
            }

            $journalEntryData = [
                'entry_date' => $run->period_end->format('Y-m-d'),
                'reference' => 'DEPR-' . $run->period_start->format('Ym'),
                'description' => __('asset_depreciation_run.journal_description', [
                    'period' => $run->period_start->format('M Y'),
                ]),
                // A posted depreciation run must leave a posted journal so the
                // expense and accumulated depreciation reach the GL (W7-05).
                'status' => 'posted',
                'lines' => $journalLines,
            ];

            $journalEntry = $this->createJournalEntryAction->execute($journalEntryData);

            $run->update([
                'status' => 'posted',
                'journal_entry_id' => $journalEntry->id,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);
        });
    }
}
