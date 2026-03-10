<?php

use App\Http\Requests\GoodsReceipts\StoreGoodsReceiptRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('goods-receipts');

test('store goods receipt requires at least one item', function () {
    $request = new StoreGoodsReceiptRequest;
    $validator = Validator::make([
        'purchase_order_id' => 1,
        'warehouse_id' => 1,
        'receipt_date' => now()->toDateString(),
        'status' => 'draft',
        'items' => [],
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
