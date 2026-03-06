<?php

use App\Http\Requests\PurchaseOrders\StorePurchaseOrderRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('purchase-orders');

test('store purchase order requires at least one item', function () {
    $request = new StorePurchaseOrderRequest();
    $validator = Validator::make([
        'supplier_id' => 1,
        'warehouse_id' => 1,
        'order_date' => now()->toDateString(),
        'currency' => 'IDR',
        'status' => 'draft',
        'items' => [],
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
