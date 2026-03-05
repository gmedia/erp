<?php

use App\Http\Requests\PurchaseRequests\StorePurchaseRequestRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('purchase-requests');

test('store purchase request requires at least one item', function () {
    $request = new StorePurchaseRequestRequest();
    $validator = Validator::make([
        'branch_id' => 1,
        'request_date' => now()->toDateString(),
        'priority' => 'normal',
        'status' => 'draft',
        'items' => [],
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
