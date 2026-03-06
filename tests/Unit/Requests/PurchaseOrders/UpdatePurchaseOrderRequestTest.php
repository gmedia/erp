<?php

use App\Http\Requests\PurchaseOrders\UpdatePurchaseOrderRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('purchase-orders');

test('update purchase order validates status enum', function () {
    $request = new UpdatePurchaseOrderRequest();
    $validator = Validator::make([
        'status' => 'invalid_status',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
