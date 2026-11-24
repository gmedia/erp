<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostSupplierBillJournalAction
{
    public const AP_CONTROL_ACCOUNT_CODE = '21100';

    /** Beban selisih harga bill vs GR (keputusan #357 butir 5). */
    public const PRICE_VARIANCE_ACCOUNT_CODE = '51000';

    /**
     * Tagihan yang sudah dikonfirmasi lalu berubah menjadi overdue/partially_paid/paid
     * tetap harus punya jurnal AP; draft/cancelled/void tidak diposting.
     *
     * @var list<string>
     */
    public const POSTABLE_STATUSES = ['confirmed', 'overdue', 'partially_paid', 'paid'];

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function execute(SupplierBill $supplierBill): ?JournalEntry
    {
        if ($supplierBill->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $supplierBill->journalEntry()->first();

            return $existing;
        }

        if (! in_array($supplierBill->status, self::POSTABLE_STATUSES, true)) {
            return null;
        }

        return DB::transaction(function () use ($supplierBill): JournalEntry {
            $supplierBill->loadMissing('items');

            if ($supplierBill->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a supplier bill with no items.',
                ]);
            }

            $entryDate = $supplierBill->bill_date->format('Y-m-d');
            $apAccount = $this->resolveControlAccount->execute(self::AP_CONTROL_ACCOUNT_CODE, $entryDate);

            $debitTotal = 0.0;
            $debitLines = [];
            $expenseGrouped = [];

            // Item bill atas GR: akun debit = akun yang benar-benar dikredit jurnal GR asalnya
            // (GR/IR untuk GR baru, 21100 untuk GR lama), supaya utang tidak tercatat ganda.
            // GR/IR dikosongkan sebesar nilai penerimaan untuk qty yang ditagih (harga GR);
            // selisih harga bill vs GR masuk 51000.
            $varianceAmount = 0.0;

            foreach ($supplierBill->items as $item) {
                $grCreditAccountId = $item->goods_receipt_item_id !== null
                    ? $this->grCreditAccountIdFor($item)
                    : null;

                if ($grCreditAccountId !== null) {
                    $grValue = $this->grValueForBillItem($item);
                    $expenseGrouped[$grCreditAccountId] = ($expenseGrouped[$grCreditAccountId] ?? 0.0) + $grValue;
                    $varianceAmount += (float) $item->line_total - $grValue;

                    continue;
                }

                $accountId = (int) $item->account_id;
                $expenseGrouped[$accountId] = ($expenseGrouped[$accountId] ?? 0.0) + (float) $item->line_total;
            }

            $varianceAmount = round($varianceAmount, 2);

            foreach ($expenseGrouped as $accountId => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                $debitLines[] = [
                    'account_id' => $accountId,
                    'debit' => round($amount, 2),
                    'credit' => 0,
                    'memo' => "Bill {$supplierBill->bill_number}",
                ];
                $debitTotal += $amount;
            }

            // Selisih harga: debit (bill lebih tinggi) atau kredit (bill lebih rendah) ke 51000.
            if ($varianceAmount !== 0.0) {
                $varianceAccount = $this->resolveControlAccount->execute(self::PRICE_VARIANCE_ACCOUNT_CODE, $entryDate);
                $debitLines[] = [
                    'account_id' => $varianceAccount->id,
                    'debit' => $varianceAmount > 0 ? $varianceAmount : 0,
                    'credit' => $varianceAmount < 0 ? -$varianceAmount : 0,
                    'memo' => "Selisih harga Bill {$supplierBill->bill_number}",
                ];
                $debitTotal += $varianceAmount;
            }

            $debitTotal = round($debitTotal, 2);

            $creditLine = [
                'account_id' => $apAccount->id,
                'debit' => 0,
                'credit' => $debitTotal,
                'memo' => "Bill {$supplierBill->bill_number}",
            ];

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $entryDate,
                'reference' => $supplierBill->bill_number,
                'description' => "Supplier Bill {$supplierBill->bill_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => SupplierBill::class,
                'source_id' => $supplierBill->id,
                'branch_id' => $supplierBill->branch_id,
                'lines' => array_merge($debitLines, [$creditLine]),
            ]);

            $supplierBill->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }

    /**
     * Nilai penerimaan GR untuk qty bill ini: qty x harga GR. Qty yang sudah ditagih
     * bill lain (yang sudah diposting) untuk item GR yang sama tidak dihitung lagi.
     */
    private function grValueForBillItem(SupplierBillItem $item): float
    {
        $grItem = GoodsReceiptItem::query()->find($item->goods_receipt_item_id);

        if ($grItem === null) {
            return 0.0;
        }

        $alreadyBilled = (float) SupplierBillItem::query()
            ->join('supplier_bills', 'supplier_bills.id', '=', 'supplier_bill_items.supplier_bill_id')
            ->where('supplier_bill_items.goods_receipt_item_id', $grItem->id)
            ->where('supplier_bill_items.supplier_bill_id', '!=', $item->supplier_bill_id)
            ->whereIn('supplier_bills.status', self::POSTABLE_STATUSES)
            ->sum('supplier_bill_items.quantity');

        $remainingQty = round((float) $grItem->quantity_accepted - $alreadyBilled, 2);
        $qty = (float) $item->quantity;

        if ($qty > $remainingQty) {
            throw ValidationException::withMessages([
                'items' => "Qty bill melebihi sisa qty GR {$grItem->id} (sisa {$remainingQty}).",
            ]);
        }

        return round($qty * (float) $grItem->unit_price, 2);
    }

    /**
     * Akun kredit pada jurnal GR terkait yang bukan AP. Null bila bill tanpa GR
     * atau GR belum punya jurnal; bill tanpa GR memakai akun item seperti biasa.
     */
    private function grCreditAccountIdFor(SupplierBillItem $item): ?int
    {
        $grItem = GoodsReceiptItem::query()->find($item->goods_receipt_item_id);

        if ($grItem === null) {
            return null;
        }

        $goodsReceipt = GoodsReceipt::query()->find($grItem->goods_receipt_id);

        if ($goodsReceipt === null || $goodsReceipt->journal_entry_id === null) {
            return null;
        }

        // Jurnal GR hanya punya satu sisi kredit (GR/IR atau AP lama); ambil akun itu apa adanya.
        $creditLine = JournalEntryLine::query()
            ->where('journal_entry_id', $goodsReceipt->journal_entry_id)
            ->where('credit', '>', 0)
            ->first();

        return $creditLine?->account_id;
    }
}
