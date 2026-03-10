<?php

use App\Http\Requests\SupplierReturns\StoreSupplierReturnRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('supplier-returns');

test('store supplier return requires at least one item', function () {
    $request = new StoreSupplierReturnRequest;
    $validator = Validator::make([
        'purchase_order_id' => 1,
        'supplier_id' => 1,
        'warehouse_id' => 1,
        'return_date' => now()->toDateString(),
        'reason' => 'defective',
        'status' => 'draft',
        'items' => [],
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
