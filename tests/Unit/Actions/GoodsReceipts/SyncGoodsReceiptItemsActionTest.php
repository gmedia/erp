<?php

use App\Actions\GoodsReceipts\SyncGoodsReceiptItemsAction;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('goods-receipts');

test('sync items action recreates items', function () {
    $goodsReceipt = GoodsReceipt::factory()->create();
    $purchaseOrderItem = PurchaseOrderItem::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $action = new SyncGoodsReceiptItemsAction;
    $action->execute($goodsReceipt, [
        [
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_received' => 5,
            'quantity_accepted' => 4,
            'quantity_rejected' => 1,
            'unit_price' => 5000,
        ],
    ]);

    expect($goodsReceipt->fresh()->items)->toHaveCount(1);
});
