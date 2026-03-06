<?php

use App\Http\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('purchase-orders');

test('purchase order resource returns expected structure', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();
    $purchaseOrder->items()->create([
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity' => 4,
        'unit_price' => 5000,
        'line_total' => 20000,
    ]);
    $purchaseOrder->load(['supplier', 'warehouse', 'approver', 'creator', 'items.product', 'items.unit']);

    $data = (new PurchaseOrderResource($purchaseOrder))->toArray(new Request());

    expect($data)->toHaveKeys([
        'id',
        'po_number',
        'supplier',
        'warehouse',
        'order_date',
        'status',
        'items',
    ]);
});
