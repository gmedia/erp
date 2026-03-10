<?php

use App\Http\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('goods-receipts');

test('goods receipt resource returns expected structure', function () {
    $goodsReceipt = GoodsReceipt::factory()->create();
    $goodsReceipt->items()->create([
        'purchase_order_item_id' => PurchaseOrderItem::factory()->create()->id,
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity_received' => 4,
        'quantity_accepted' => 4,
        'quantity_rejected' => 0,
        'unit_price' => 5000,
    ]);
    $goodsReceipt->load(['purchaseOrder.supplier', 'warehouse', 'receiver', 'confirmer', 'creator', 'items.product', 'items.unit']);

    $data = (new GoodsReceiptResource($goodsReceipt))->toArray(new Request());

    expect($data)->toHaveKeys([
        'id',
        'gr_number',
        'purchase_order',
        'warehouse',
        'receipt_date',
        'status',
        'items',
    ]);
});
