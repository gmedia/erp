<?php

use App\Http\Requests\SupplierReturns\IndexSupplierReturnRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('supplier-returns');

test('index supplier return validates sort field', function () {
    $request = new IndexSupplierReturnRequest;
    $validator = Validator::make([
        'sort_by' => 'invalid_field',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
