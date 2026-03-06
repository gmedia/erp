<?php

use App\Http\Requests\PurchaseOrders\ExportPurchaseOrderRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('purchase-orders');

test('export purchase order validates direction enum', function () {
    $request = new ExportPurchaseOrderRequest();
    $validator = Validator::make([
        'sort_direction' => 'up',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
