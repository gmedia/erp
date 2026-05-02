<?php

namespace App\Actions\ArReceipts;

use App\Actions\Concerns\RecreatesItems;
use App\Models\ArReceipt;

class SyncArReceiptAllocationsAction
{
    use RecreatesItems;

    public function execute(ArReceipt $arReceipt, array $allocations): void
    {
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
    }
}
