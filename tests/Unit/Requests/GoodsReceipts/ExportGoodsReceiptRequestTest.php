<?php

use App\Http\Requests\GoodsReceipts\ExportGoodsReceiptRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('goods-receipts');

test('export goods receipt validates direction enum', function () {
    $request = new ExportGoodsReceiptRequest();
    $validator = Validator::make([
        'sort_direction' => 'up',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
