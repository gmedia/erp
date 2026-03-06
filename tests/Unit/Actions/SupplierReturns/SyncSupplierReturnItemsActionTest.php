<?php

use App\Actions\SupplierReturns\SyncSupplierReturnItemsAction;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\SupplierReturn;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('supplier-returns');

test('sync items action recreates items', function () {
    $supplierReturn = SupplierReturn::factory()->create();
    $goodsReceiptItem = GoodsReceiptItem::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $action = new SyncSupplierReturnItemsAction();
    $action->execute($supplierReturn, [
        [
            'goods_receipt_item_id' => $goodsReceiptItem->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_returned' => 5,
            'unit_price' => 5000,
        ],
    ]);

    expect($supplierReturn->fresh()->items)->toHaveCount(1);
});
