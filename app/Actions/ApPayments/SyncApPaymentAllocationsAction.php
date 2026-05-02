<?php

namespace App\Actions\ApPayments;

use App\Actions\Concerns\RecreatesItems;
use App\Models\ApPayment;

class SyncApPaymentAllocationsAction
{
    use RecreatesItems;

    public function execute(ApPayment $apPayment, array $allocations): void
    {
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
    }
}
