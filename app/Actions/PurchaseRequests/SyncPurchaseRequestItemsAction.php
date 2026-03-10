<?php

namespace App\Actions\PurchaseRequests;

use App\Models\PurchaseRequest;
use Illuminate\Support\Collection;

class SyncPurchaseRequestItemsAction
{
    public function execute(PurchaseRequest $purchaseRequest, array $items): void
    {
        $normalized = Collection::make($items)->map(static function (array $item): array {
            $estimatedUnitPrice = $item['estimated_unit_price'] ?? null;
            $quantity = (float) $item['quantity'];

            return [
                'product_id' => (int) $item['product_id'],
                'unit_id' => (int) $item['unit_id'],
                'quantity' => $quantity,
                'quantity_ordered' => 0,
                'estimated_unit_price' => $estimatedUnitPrice,
                'estimated_total' => $estimatedUnitPrice !== null ? $quantity * (float) $estimatedUnitPrice : null,
                'notes' => $item['notes'] ?? null,
            ];
        })->values()->all();

        $purchaseRequest->items()->delete();
        $purchaseRequest->items()->createMany($normalized);

        $estimatedAmount = (string) collect($normalized)
            ->sum(static fn (array $row) => (float) ($row['estimated_total'] ?? 0));

        $purchaseRequest->update(['estimated_amount' => $estimatedAmount]);
    }
}
