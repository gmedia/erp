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
    $product = Product::factory()->create(['name' => 'Printer Paper']);
    $unit = Unit::factory()->create(['name' => 'Ream']);
    $purchaseOrder->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 4,
        'unit_price' => 5000,
        'line_total' => 20000,
        'notes' => 'Office restock',
    ]);
    $purchaseOrder->load(['supplier', 'warehouse', 'approver', 'creator', 'items.product', 'items.unit']);

    $data = (new PurchaseOrderResource($purchaseOrder))->toArray(new Request);

    expect($data)->toHaveKeys([
        'id',
        'po_number',
        'supplier',
        'warehouse',
        'order_date',
        'status',
        'items',
    ]);

    expect($data['items'][0])->toMatchArray([
        'product' => [
            'id' => $product->id,
            'name' => 'Printer Paper',
        ],
        'unit' => [
            'id' => $unit->id,
            'name' => 'Ream',
        ],
        'quantity' => '4.00',
        'unit_price' => '5000.00',
        'line_total' => '20000.00',
        'notes' => 'Office restock',
    ]);
});
