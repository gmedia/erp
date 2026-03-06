<?php

use App\Http\Requests\GoodsReceipts\IndexGoodsReceiptRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('goods-receipts');

test('index goods receipt validates sort field', function () {
    $request = new IndexGoodsReceiptRequest();
    $validator = Validator::make([
        'sort_by' => 'invalid_field',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
