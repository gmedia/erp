<?php

namespace App\Actions\ApPayments;

use App\Actions\Concerns\RecreatesItems;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\SupplierBill;
use Illuminate\Support\Facades\DB;

class SyncApPaymentAllocationsAction
{
    use RecreatesItems;

    public function execute(ApPayment $apPayment, array $allocations): void
    {
        DB::transaction(function () use ($apPayment, $allocations): void {
            $normalized = $this->recreateItems($apPayment->allocations(), $allocations, static function (array $allocation): array {
                return [
                    'supplier_bill_id' => (int) $allocation['supplier_bill_id'],
                    'allocated_amount' => (float) $allocation['allocated_amount'],
                    'discount_taken' => (float) ($allocation['discount_taken'] ?? 0),
                    'notes' => $allocation['notes'] ?? null,
                ];
            });

            $totalAllocated = collect($normalized)->sum(static fn (array $row) => (float) $row['allocated_amount']);
            $totalAmount = (float) $apPayment->total_amount;

            $apPayment->update([
                'total_allocated' => (string) $totalAllocated,
                'total_unallocated' => (string) ($totalAmount - $totalAllocated),
            ]);

            // Sync each affected bill's amount_paid and amount_due
            $affectedBillIds = collect($normalized)->pluck('supplier_bill_id')->unique();
            foreach ($affectedBillIds as $billId) {
                $bill = SupplierBill::where('id', $billId)->lockForUpdate()->first();
                if ($bill === null) {
                    continue;
                }

                $totalPaid = ApPaymentAllocation::where('supplier_bill_id', $billId)->sum('allocated_amount');
                $bill->update([
                    'amount_paid' => (string) $totalPaid,
                    'amount_due' => (string) ((float) $bill->grand_total - $totalPaid),
                ]);
                $bill->updatePaymentStatus();
            }
        });
    }
}
