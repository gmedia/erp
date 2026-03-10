<?php

use App\Http\Requests\PurchaseRequests\IndexPurchaseRequestRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('purchase-requests');

test('index purchase request validates allowed sort values', function () {
    $request = new IndexPurchaseRequestRequest;
    $validator = Validator::make([
        'sort_by' => 'not_allowed',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
