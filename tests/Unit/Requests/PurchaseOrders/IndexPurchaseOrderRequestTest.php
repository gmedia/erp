<?php

use App\Http\Requests\PurchaseOrders\IndexPurchaseOrderRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('purchase-orders');

test('index purchase order validates sort field', function () {
    $request = new IndexPurchaseOrderRequest();
    $validator = Validator::make([
        'sort_by' => 'invalid_field',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
