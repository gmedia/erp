<?php

namespace App\Actions\ArReceipts;

use App\Actions\Concerns\RecreatesItems;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\CustomerInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncArReceiptAllocationsAction
{
    use RecreatesItems;

    /** Receipt statuses whose allocations reduce invoice balances (has journal). */
    public const POSTED_STATUSES = ['confirmed', 'reconciled'];

    public function execute(ArReceipt $arReceipt, array $allocations): void
    {
        DB::transaction(function () use ($arReceipt, $allocations) {
            $previousInvoiceIds = $arReceipt->allocations()->pluck('customer_invoice_id');

            if (in_array($arReceipt->status, self::POSTED_STATUSES, true)) {
                $this->assertWithinOutstanding($arReceipt, $allocations);
            }

            $normalized = $this->recreateItems(
                $arReceipt->allocations(),
                $allocations,
                static function (array $allocation): array {
                    return [
                        'customer_invoice_id' => (int) $allocation['customer_invoice_id'],
                        'allocated_amount' => (float) $allocation['allocated_amount'],
                        'discount_given' => (float) ($allocation['discount_given'] ?? 0),
                        'notes' => $allocation['notes'] ?? null,
                    ];
                }
            );

            $totalAllocated = collect($normalized)->sum(static fn (array $row) => (float) $row['allocated_amount']);
            $totalAmount = (float) $arReceipt->total_amount;
            $totalUnallocated = $totalAmount - $totalAllocated;

            $arReceipt->update([
                'total_allocated' => (string) $totalAllocated,
                'total_unallocated' => (string) $totalUnallocated,
            ]);

            // Sync invoices touched now or before (e.g. allocation removed/cancelled)
            $affectedInvoiceIds = collect($normalized)->pluck('customer_invoice_id')
                ->merge($previousInvoiceIds)
                ->unique();
            $this->refreshInvoices($affectedInvoiceIds);
        });
    }

    /**
     * Recalculate amount_received/amount_due from POSTED receipts only.
     * Draft, cancelled and void receipts never reduce an invoice's balance.
     *
     * @param  iterable<int, int|string>  $invoiceIds
     */
    public function refreshInvoices(iterable $invoiceIds): void
    {
        foreach (collect($invoiceIds)->unique() as $invoiceId) {
            $invoice = CustomerInvoice::where('id', $invoiceId)->lockForUpdate()->first();
            if (! $invoice) {
                continue;
            }

            $totalReceived = ArReceiptAllocation::query()
                ->where('customer_invoice_id', $invoiceId)
                ->whereHas('receipt', fn ($q) => $q->whereIn('status', self::POSTED_STATUSES))
                ->sum('allocated_amount');
            $amountDue = (float) $invoice->grand_total
                - $totalReceived
                - (float) $invoice->credit_note_amount;
            $invoice->update([
                'amount_received' => (string) $totalReceived,
                'amount_due' => (string) $amountDue,
            ]);
            $invoice->updatePaymentStatus();
        }
    }

    /**
     * Re-check overflow at confirm time, under a row lock, against POSTED receipts
     * only (other drafts do not bind the quota). Prevents two drafts that each fit
     * the outstanding amount from both being confirmed.
     *
     * @param  array<int, array<string, mixed>>  $allocations
     */
    private function assertWithinOutstanding(ArReceipt $arReceipt, array $allocations): void
    {
        $requested = collect($allocations)
            ->groupBy(fn (array $row) => (int) $row['customer_invoice_id'])
            ->map(fn ($rows) => $rows->sum(fn (array $row) => (float) $row['allocated_amount']));

        foreach ($requested as $invoiceId => $amount) {
            $invoice = CustomerInvoice::where('id', $invoiceId)->lockForUpdate()->first();
            if (! $invoice) {
                continue;
            }

            $postedOthers = ArReceiptAllocation::query()
                ->where('customer_invoice_id', $invoiceId)
                ->where('ar_receipt_id', '!=', $arReceipt->id)
                ->whereHas('receipt', fn ($q) => $q->whereIn('status', self::POSTED_STATUSES))
                ->sum('allocated_amount');

            $max = (float) $invoice->grand_total - (float) $postedOthers - (float) $invoice->credit_note_amount;

            if ($amount > $max + 0.00001) {
                throw ValidationException::withMessages([
                    'allocations' => ["Allocation exceeds invoice outstanding amount. Maximum: {$max}"],
                ]);
            }
        }
    }
}
