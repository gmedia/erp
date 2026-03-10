<?php

use App\Http\Requests\PurchaseRequests\UpdatePurchaseRequestRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('purchase-requests');

test('update purchase request validates status enum', function () {
    $request = new UpdatePurchaseRequestRequest;
    $validator = Validator::make([
        'status' => 'invalid_status',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
