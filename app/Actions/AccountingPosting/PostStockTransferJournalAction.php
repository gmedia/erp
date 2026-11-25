<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jurnal transfer antar gudang (issue #495).
 *
 * Barang dalam perjalanan dicatat di akun "Persediaan dalam Perjalanan" (Setting
 * inventory_in_transit_account_id). Setiap tahap punya jurnal sendiri, dibedakan
 * lewat journal_type dan ditautkan ke transfer lewat source_type/source_id:
 *
 * - stock_transfer_dispatch: Dr In-Transit / Cr Persediaan asal, qty_kirim x avg asal.
 * - stock_transfer_receive: Dr Persediaan tujuan / Cr In-Transit, qty_terima x biaya kirim.
 * - stock_transfer_shortage: Dr Beban Selisih / Cr In-Transit, (qty_kirim - qty_terima) x biaya kirim.
 * - stock_transfer_dispatch_reversal: pembalik dispatch bila dibatalkan sebelum diterima.
 *
 * Setiap tahap idempoten. journal_entry_id transfer menunjuk jurnal dispatch.
 */
class PostStockTransferJournalAction
{
    public const INVENTORY_ACCOUNT_CODE = '11300';

    public const SHORTAGE_EXPENSE_CODE = PostStockAdjustmentJournalAction::STOCK_ADJUSTMENT_EXPENSE_CODE;

    // journal_type berkolom varchar(20): nama tipe harus <= 20 karakter.
    public const TYPE_DISPATCH = 'st_dispatch';

    public const TYPE_RECEIVE = 'st_receive';

    public const TYPE_SHORTAGE = 'st_shortage';

    public const TYPE_DISPATCH_REVERSAL = 'st_dispatch_rev';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    /**
     * Dispatch: barang keluar dari gudang asal dan masuk persediaan dalam perjalanan.
     */
    public function executeDispatch(StockTransfer $stockTransfer): ?JournalEntry
    {
        return $this->postOnce($stockTransfer, self::TYPE_DISPATCH, function () use ($stockTransfer): ?JournalEntry {
            $stockTransfer->loadMissing('items', 'fromWarehouse');

            $total = 0.0;

            foreach ($stockTransfer->items as $item) {
                $total += (float) $item->quantity * $this->dispatchUnitCost($stockTransfer, (int) $item->id);
            }

            $total = round($total, 2);

            if ($total <= 0) {
                return null;
            }

            $entryDate = $stockTransfer->transfer_date->format('Y-m-d');
            $transitAccount = $this->inTransitAccount($entryDate);
            $inventoryAccount = $this->resolveControlAccount->execute(self::INVENTORY_ACCOUNT_CODE, $entryDate);
            $memo = "Transfer {$stockTransfer->transfer_number}";

            return $this->post($stockTransfer, self::TYPE_DISPATCH, $entryDate, [
                $this->line($transitAccount->id, $total, 0, $memo),
                $this->line($inventoryAccount->id, 0, $total, $memo),
            ], $stockTransfer->fromWarehouse->branch_id);
        });
    }

    /**
     * Receive penuh/sebagian: barang masuk persediaan tujuan dan keluar dari in-transit.
     */
    public function executeReceive(StockTransfer $stockTransfer): ?JournalEntry
    {
        return $this->postOnce($stockTransfer, self::TYPE_RECEIVE, function () use ($stockTransfer): ?JournalEntry {
            $stockTransfer->loadMissing('items', 'toWarehouse');

            $total = 0.0;

            foreach ($stockTransfer->items as $item) {
                $total += (float) $item->quantity_received * $this->dispatchUnitCost($stockTransfer, (int) $item->id);
            }

            $total = round($total, 2);

            if ($total <= 0) {
                return null;
            }

            $entryDate = ($stockTransfer->received_at ?? $stockTransfer->transfer_date)->format('Y-m-d');
            $transitAccount = $this->inTransitAccount($entryDate);
            $inventoryAccount = $this->resolveControlAccount->execute(self::INVENTORY_ACCOUNT_CODE, $entryDate);
            $memo = "Transfer {$stockTransfer->transfer_number}";

            return $this->post($stockTransfer, self::TYPE_RECEIVE, $entryDate, [
                $this->line($inventoryAccount->id, $total, 0, $memo),
                $this->line($transitAccount->id, 0, $total, $memo),
            ], $stockTransfer->toWarehouse->branch_id);
        });
    }

    /**
     * Selisih kurang saat diterima: Dr Beban Selisih / Cr In-Transit.
     */
    public function executeShortage(StockTransfer $stockTransfer): ?JournalEntry
    {
        return $this->postOnce($stockTransfer, self::TYPE_SHORTAGE, function () use ($stockTransfer): ?JournalEntry {
            $stockTransfer->loadMissing('items', 'toWarehouse');

            $shortage = 0.0;

            foreach ($stockTransfer->items as $item) {
                $missing = (float) $item->quantity - (float) $item->quantity_received;

                if ($missing > 0) {
                    $shortage += $missing * $this->dispatchUnitCost($stockTransfer, (int) $item->id);
                }
            }

            $shortage = round($shortage, 2);

            if ($shortage <= 0) {
                return null;
            }

            $entryDate = ($stockTransfer->received_at ?? $stockTransfer->transfer_date)->format('Y-m-d');
            $transitAccount = $this->inTransitAccount($entryDate);
            $shortageAccount = $this->resolveControlAccount->execute(self::SHORTAGE_EXPENSE_CODE, $entryDate);
            $memo = "Transfer {$stockTransfer->transfer_number}";

            return $this->post($stockTransfer, self::TYPE_SHORTAGE, $entryDate, [
                $this->line($shortageAccount->id, $shortage, 0, $memo),
                $this->line($transitAccount->id, 0, $shortage, $memo),
            ], $stockTransfer->toWarehouse->branch_id);
        });
    }

    /**
     * Batal sebelum diterima: jurnal pembalik dari jurnal dispatch.
     */
    public function executeDispatchReversal(StockTransfer $stockTransfer): ?JournalEntry
    {
        $post = function () use ($stockTransfer): ?JournalEntry {
            $dispatch = JournalEntry::query()
                ->where('source_type', StockTransfer::class)
                ->where('source_id', $stockTransfer->id)
                ->where('journal_type', self::TYPE_DISPATCH)
                ->with('lines')
                ->first();

            if ($dispatch === null) {
                return null;
            }

            $memo = "Transfer {$stockTransfer->transfer_number}";
            $lines = $dispatch->lines->map(fn ($line): array => $this->line(
                (int) $line->account_id,
                (float) $line->credit,
                (float) $line->debit,
                $memo,
            ))->all();

            return $this->post(
                $stockTransfer,
                self::TYPE_DISPATCH_REVERSAL,
                now()->format('Y-m-d'),
                $lines,
                $dispatch->branch_id,
            );
        };

        return $this->postOnce($stockTransfer, self::TYPE_DISPATCH_REVERSAL, $post);
    }

    /**
     * Biaya per unit untuk satu baris transfer: movement transfer_out (avg asal saat keluar).
     */
    private function dispatchUnitCost(StockTransfer $stockTransfer, int $itemId): float
    {
        $cost = StockMovement::query()
            ->where('reference_type', StockTransfer::class)
            ->where('reference_id', $stockTransfer->id)
            ->where('movement_type', 'transfer_out')
            ->where('source_line_id', $itemId)
            ->value('unit_cost');

        return (float) ($cost ?? 0);
    }

    /**
     * Idempoten per tahap: bila jurnal tahap ini sudah ada, dikembalikan tanpa posting ulang.
     *
     * @param  callable(): ?JournalEntry  $post
     */
    private function postOnce(StockTransfer $stockTransfer, string $journalType, callable $post): ?JournalEntry
    {
        return DB::transaction(function () use ($stockTransfer, $journalType, $post): ?JournalEntry {
            $existing = JournalEntry::query()
                ->where('source_type', StockTransfer::class)
                ->where('source_id', $stockTransfer->id)
                ->where('journal_type', $journalType)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return $post();
        });
    }

    /**
     * @param  array<int, array{account_id:int, debit:float, credit:float, memo:string}>  $lines
     */
    private function post(
        StockTransfer $stockTransfer,
        string $journalType,
        string $entryDate,
        array $lines,
        ?int $branchId,
    ): JournalEntry {
        $journalEntry = $this->createJournalEntry->execute([
            'entry_date' => $entryDate,
            'reference' => $stockTransfer->transfer_number,
            'description' => $this->description(
                $journalType,
                $stockTransfer->transfer_number,
                (int) $stockTransfer->id,
            ),
            'status' => 'posted',
            'journal_type' => $journalType,
            'source_type' => StockTransfer::class,
            'source_id' => $stockTransfer->id,
            'branch_id' => $branchId,
            'lines' => $lines,
        ]);

        if ($journalType === self::TYPE_DISPATCH && $stockTransfer->journal_entry_id === null) {
            $stockTransfer->forceFill(['journal_entry_id' => $journalEntry->id])->save();
        }

        return $journalEntry;
    }

    private function inTransitAccount(string $entryDate): Account
    {
        $accountId = Setting::get('inventory_in_transit_account_id');

        if ($accountId === null || $accountId === '') {
            throw ValidationException::withMessages([
                'account' => __('accounting_posting.in_transit_account_missing'),
            ]);
        }

        // Akun dipilih lewat Setting, tetapi tetap harus aktif dan berasal dari COA
        // yang berlaku untuk tanggal jurnal (satu sumber kebenaran dengan akun kontrol lain).
        $version = $this->resolveControlAccount->resolveVersion($entryDate);

        $account = Account::query()
            ->where('id', (int) $accountId)
            ->where('coa_version_id', $version->id)
            ->where('is_active', true)
            ->first();

        if ($account === null) {
            throw ValidationException::withMessages([
                'account' => __('accounting_posting.in_transit_account_missing'),
            ]);
        }

        return $account;
    }

    /**
     * @return array{account_id:int, debit:float, credit:float, memo:string}
     */
    private function line(int $accountId, float $debit, float $credit, string $memo): array
    {
        return [
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
            'memo' => $memo,
        ];
    }

    private function description(string $journalType, ?string $transferNumber, int $transferId): string
    {
        // Nomor dokumen dibuat saat store; cadangan id menjaga deskripsi tetap terisi.
        $label = $transferNumber ?? "#{$transferId}";

        return match ($journalType) {
            self::TYPE_DISPATCH => "Transfer keluar {$label}",
            self::TYPE_RECEIVE => "Transfer diterima {$label}",
            self::TYPE_SHORTAGE => "Selisih transfer {$label}",
            self::TYPE_DISPATCH_REVERSAL => "Pembalik transfer {$label}",
            default => "Transfer {$label}",
        };
    }
}
