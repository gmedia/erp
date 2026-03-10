<?php

use App\Http\Requests\SupplierReturns\UpdateSupplierReturnRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('supplier-returns');

test('update supplier return validates status enum', function () {
    $request = new UpdateSupplierReturnRequest();
    $validator = Validator::make([
        'status' => 'invalid_status',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
