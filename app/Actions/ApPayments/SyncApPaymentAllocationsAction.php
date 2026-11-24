<?php

namespace App\Actions\ApPayments;

use App\Actions\Concerns\RecreatesItems;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\SupplierBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncApPaymentAllocationsAction
{
    use RecreatesItems;

    /** Payment statuses whose allocations reduce bill balances (has journal). */
    public const POSTED_STATUSES = ['confirmed', 'reconciled'];

    public function execute(ApPayment $apPayment, array $allocations): void
    {
        DB::transaction(function () use ($apPayment, $allocations): void {
            $previousBillIds = $apPayment->allocations()->pluck('supplier_bill_id');

            if (in_array($apPayment->status, self::POSTED_STATUSES, true)) {
                $this->assertWithinOutstanding($apPayment, $allocations);
            }

            $normalized = $this->recreateItems(
                $apPayment->allocations(),
                $allocations,
                static function (array $allocation): array {
                    return [
                        'supplier_bill_id' => (int) $allocation['supplier_bill_id'],
                        'allocated_amount' => (float) $allocation['allocated_amount'],
                        'discount_taken' => (float) ($allocation['discount_taken'] ?? 0),
                        'notes' => $allocation['notes'] ?? null,
                    ];
                },
            );

            $totalAllocated = collect($normalized)->sum(static fn (array $row) => (float) $row['allocated_amount']);
            $totalAmount = (float) $apPayment->total_amount;

            $apPayment->update([
                'total_allocated' => (string) $totalAllocated,
                'total_unallocated' => (string) ($totalAmount - $totalAllocated),
            ]);

            // Sync bills touched now or before (e.g. allocation removed/cancelled)
            $affectedBillIds = collect($normalized)->pluck('supplier_bill_id')
                ->merge($previousBillIds)
                ->unique();
            $this->refreshBills($affectedBillIds);
        });
    }

    /**
     * Recalculate amount_paid/amount_due from POSTED payments only.
     * Draft, pending, cancelled and void payments never reduce a bill's balance.
     *
     * @param  iterable<int, int|string>  $billIds
     */
    public function refreshBills(iterable $billIds): void
    {
        foreach (collect($billIds)->unique() as $billId) {
            $bill = SupplierBill::where('id', $billId)->lockForUpdate()->first();
            if ($bill === null) {
                continue;
            }

            // Issue #437: diskon alokasi ikut mengurangi sisa bill (settlement = kas + diskon).
            $totalPaid = ApPaymentAllocation::query()
                ->where('supplier_bill_id', $billId)
                ->whereHas('payment', fn ($q) => $q->whereIn('status', self::POSTED_STATUSES))
                ->selectRaw('COALESCE(SUM(allocated_amount + discount_taken), 0) as settled')
                ->value('settled');

            $bill->update([
                'amount_paid' => (string) $totalPaid,
                'amount_due' => (string) ((float) $bill->grand_total - $totalPaid),
            ]);
            $bill->updatePaymentStatus();
        }
    }

    /**
     * Confirm without an allocations payload: re-check the stored allocations so an
     * overflowing draft cannot be confirmed by a status-only PUT.
     */
    public function assertStoredWithinOutstanding(ApPayment $apPayment): void
    {
        $stored = $apPayment->allocations()
            ->get(['supplier_bill_id', 'allocated_amount', 'discount_taken'])
            ->toArray();

        $this->assertWithinOutstanding($apPayment, $stored);
    }

    /**
     * Re-check overflow at confirm time, under a row lock, against POSTED payments
     * only (other drafts do not bind the quota).
     *
     * @param  array<int, array<string, mixed>>  $allocations
     */
    private function assertWithinOutstanding(ApPayment $apPayment, array $allocations): void
    {
        // Issue #437: setiap alokasi memakan kas + diskon dari sisa bill.
        $requested = collect($allocations)
            ->groupBy(fn (array $row) => (int) $row['supplier_bill_id'])
            ->map(fn ($rows) => $rows->sum(fn (array $row) => (float) $row['allocated_amount']
                + (float) ($row['discount_taken'] ?? 0)));

        foreach ($requested as $billId => $amount) {
            $bill = SupplierBill::where('id', $billId)->lockForUpdate()->first();
            if ($bill === null) {
                continue;
            }

            $postedOthers = ApPaymentAllocation::query()
                ->where('supplier_bill_id', $billId)
                ->where('ap_payment_id', '!=', $apPayment->id)
                ->whereHas('payment', fn ($q) => $q->whereIn('status', self::POSTED_STATUSES))
                ->selectRaw('COALESCE(SUM(allocated_amount + discount_taken), 0) as settled')
                ->value('settled');

            $max = (float) $bill->grand_total - (float) $postedOthers;

            if ($amount > $max + 0.00001) {
                throw ValidationException::withMessages([
                    'allocations' => ["Allocation exceeds bill outstanding amount. Maximum: {$max}"],
                ]);
            }
        }
    }
}
