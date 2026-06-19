<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\JournalEntry;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostStockAdjustmentJournalAction
{
    public const INVENTORY_ACCOUNT_CODE = '11300';

    public const STOCK_ADJUSTMENT_EXPENSE_CODE = '51000';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function execute(StockAdjustment $stockAdjustment): ?JournalEntry
    {
        if ($stockAdjustment->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $stockAdjustment->journalEntry()->first();

            return $existing;
        }

        if ($stockAdjustment->status !== 'approved') {
            return null;
        }

        return DB::transaction(function () use ($stockAdjustment): JournalEntry {
            $stockAdjustment->loadMissing('items', 'warehouse');

            if ($stockAdjustment->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a stock adjustment with no items.',
                ]);
            }

            $inventoryAccount = $this->resolveControlAccount->execute(self::INVENTORY_ACCOUNT_CODE);
            $expenseAccount = $this->resolveControlAccount->execute(self::STOCK_ADJUSTMENT_EXPENSE_CODE);

            $totalPositive = 0.0;
            $totalNegative = 0.0;

            foreach ($stockAdjustment->items as $item) {
                $cost = abs((float) $item->total_cost);

                if ((float) $item->quantity_adjusted >= 0) {
                    $totalPositive += $cost;
                } else {
                    $totalNegative += $cost;
                }
            }

            $totalPositive = round($totalPositive, 2);
            $totalNegative = round($totalNegative, 2);

            $lines = [];
            $memo = "Adj {$stockAdjustment->adjustment_number}";

            if ($totalNegative > 0) {
                $lines[] = [
                    'account_id' => $expenseAccount->id,
                    'debit' => $totalNegative,
                    'credit' => 0,
                    'memo' => $memo,
                ];
                $lines[] = [
                    'account_id' => $inventoryAccount->id,
                    'debit' => 0,
                    'credit' => $totalNegative,
                    'memo' => $memo,
                ];
            }

            if ($totalPositive > 0) {
                $lines[] = [
                    'account_id' => $inventoryAccount->id,
                    'debit' => $totalPositive,
                    'credit' => 0,
                    'memo' => $memo,
                ];
                $lines[] = [
                    'account_id' => $expenseAccount->id,
                    'debit' => 0,
                    'credit' => $totalPositive,
                    'memo' => $memo,
                ];
            }

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $stockAdjustment->adjustment_date->format('Y-m-d'),
                'reference' => $stockAdjustment->adjustment_number,
                'description' => "Stock Adjustment {$stockAdjustment->adjustment_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => StockAdjustment::class,
                'source_id' => $stockAdjustment->id,
                'branch_id' => $stockAdjustment->warehouse->branch_id,
                'lines' => $lines,
            ]);

            $stockAdjustment->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }
}
