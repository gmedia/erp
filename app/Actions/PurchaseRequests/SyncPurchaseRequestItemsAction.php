<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Concerns\RecreatesItems;
use App\Actions\Concerns\RoundsLineAmounts;
use App\Models\PurchaseRequest;

class SyncPurchaseRequestItemsAction
{
    use RecreatesItems;
    use RoundsLineAmounts;

    public function execute(PurchaseRequest $purchaseRequest, array $items): void
    {
        $normalized = $this->recreateItems($purchaseRequest->items(), $items, function (array $item): array {
            $estimatedUnitPrice = $item['estimated_unit_price'] ?? null;
            $quantity = (float) $item['quantity'];

            return [
                'product_id' => (int) $item['product_id'],
                'unit_id' => (int) $item['unit_id'],
                'quantity' => $quantity,
                'quantity_ordered' => 0,
                'estimated_unit_price' => $estimatedUnitPrice,
                'estimated_total' => $estimatedUnitPrice !== null
                    ? $this->roundLineAmount($quantity * (float) $estimatedUnitPrice)
                    : null,
                'notes' => $item['notes'] ?? null,
            ];
        });

        $estimatedAmount = $this->sumRoundedLineAmounts(array_map(
            static fn (array $row): float => (float) ($row['estimated_total'] ?? 0),
            $normalized,
        ));

        $purchaseRequest->update(['estimated_amount' => (string) $estimatedAmount]);
    }
}
