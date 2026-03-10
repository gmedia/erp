<?php

use App\Http\Requests\PurchaseRequests\ExportPurchaseRequestRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('purchase-requests');

test('export purchase request validates direction enum', function () {
    $request = new ExportPurchaseRequestRequest;
    $validator = Validator::make([
        'sort_direction' => 'up',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
