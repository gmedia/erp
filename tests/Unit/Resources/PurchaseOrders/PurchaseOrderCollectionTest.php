<?php

use App\Http\Resources\PurchaseOrders\PurchaseOrderCollection;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('purchase-orders');

test('purchase order collection wraps resources', function () {
    $rows = PurchaseOrder::factory()->count(2)->create();
    $rows->load(['supplier', 'warehouse', 'approver', 'creator', 'items.product', 'items.unit']);

    $collection = new PurchaseOrderCollection($rows);
    $data = $collection->toArray(new Request());

    expect($data)->toHaveCount(2)
        ->and($data[0])->toHaveKey('id');
});
