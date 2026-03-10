<?php

use App\Http\Requests\SupplierReturns\ExportSupplierReturnRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('supplier-returns');

test('export supplier return validates direction enum', function () {
    $request = new ExportSupplierReturnRequest();
    $validator = Validator::make([
        'sort_direction' => 'up',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
