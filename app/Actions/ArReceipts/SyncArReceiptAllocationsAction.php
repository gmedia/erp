<?php

namespace App\Actions\ArReceipts;

use App\Actions\Concerns\RecreatesItems;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\CustomerInvoice;
use Illuminate\Support\Facades\DB;

class SyncArReceiptAllocationsAction
{
    use RecreatesItems;

    public function execute(ArReceipt $arReceipt, array $allocations): void
    {
        DB::transaction(function () use ($arReceipt, $allocations) {
            $normalized = $this->recreateItems($arReceipt->allocations(), $allocations, static function (array $allocation): array {
                return [
                    'customer_invoice_id' => (int) $allocation['customer_invoice_id'],
                    'allocated_amount' => (float) $allocation['allocated_amount'],
                    'discount_given' => (float) ($allocation['discount_given'] ?? 0),
                    'notes' => $allocation['notes'] ?? null,
                ];
            });

            $totalAllocated = collect($normalized)->sum(static fn (array $row) => (float) $row['allocated_amount']);
            $totalAmount = (float) $arReceipt->total_amount;
            $totalUnallocated = $totalAmount - $totalAllocated;

            $arReceipt->update([
                'total_allocated' => (string) $totalAllocated,
                'total_unallocated' => (string) $totalUnallocated,
            ]);

            // Sync each affected invoice's amount_received and amount_due
            $affectedInvoiceIds = collect($normalized)->pluck('customer_invoice_id')->unique();
            foreach ($affectedInvoiceIds as $invoiceId) {
                $invoice = CustomerInvoice::where('id', $invoiceId)->lockForUpdate()->first();
                if (! $invoice) {
                    continue;
                }

                $totalReceived = ArReceiptAllocation::where('customer_invoice_id', $invoiceId)->sum('allocated_amount');
                $invoice->update([
                    'amount_received' => (string) $totalReceived,
                    'amount_due' => (string) ((float) $invoice->grand_total - $totalReceived - (float) $invoice->credit_note_amount),
                ]);
                $invoice->updatePaymentStatus();
            }
        });
    }
}
