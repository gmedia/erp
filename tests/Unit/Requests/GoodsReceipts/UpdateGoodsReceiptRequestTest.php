<?php

use App\Http\Requests\GoodsReceipts\UpdateGoodsReceiptRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('goods-receipts');

test('update goods receipt validates status enum', function () {
    $request = new UpdateGoodsReceiptRequest();
    $validator = Validator::make([
        'status' => 'invalid_status',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
