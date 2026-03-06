<?php

namespace App\Actions\SupplierReturns;

use App\Models\SupplierReturn;
use Illuminate\Support\Collection;

class SyncSupplierReturnItemsAction
{
    public function execute(SupplierReturn $supplierReturn, array $items): void
    {
        $normalized = Collection::make($items)->map(static function (array $item): array {
            return [
                'goods_receipt_item_id' => (int) $item['goods_receipt_item_id'],
                'product_id' => (int) $item['product_id'],
                'unit_id' => array_key_exists('unit_id', $item) && $item['unit_id'] !== null && $item['unit_id'] !== ''
                    ? (int) $item['unit_id']
                    : null,
                'quantity_returned' => (float) $item['quantity_returned'],
                'unit_price' => (float) $item['unit_price'],
                'notes' => $item['notes'] ?? null,
            ];
        })->values()->all();

        $supplierReturn->items()->delete();
        $supplierReturn->items()->createMany($normalized);
    }
}
