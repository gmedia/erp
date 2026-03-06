<?php

use App\Http\Resources\SupplierReturns\SupplierReturnResource;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\SupplierReturn;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('supplier-returns');

test('supplier return resource returns expected structure', function () {
    $supplierReturn = SupplierReturn::factory()->create();
    $supplierReturn->items()->create([
        'goods_receipt_item_id' => GoodsReceiptItem::factory()->create()->id,
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity_returned' => 4,
        'unit_price' => 5000,
    ]);
    $supplierReturn->load(['purchaseOrder', 'goodsReceipt', 'supplier', 'warehouse', 'creator', 'items.product', 'items.unit']);

    $data = (new SupplierReturnResource($supplierReturn))->toArray(new Request());

    expect($data)->toHaveKeys([
        'id',
        'return_number',
        'purchase_order',
        'supplier',
        'warehouse',
        'return_date',
        'status',
        'items',
    ]);
});
