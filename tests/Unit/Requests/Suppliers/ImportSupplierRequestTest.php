<?php

use App\Http\Requests\Suppliers\ImportSupplierRequest;

uses()->group('suppliers');

test('import supplier request authorizes access', function () {
    $request = new ImportSupplierRequest;

    expect($request->authorize())->toBeTrue();
});

test('import supplier request returns correct validation rules', function () {
    $request = new ImportSupplierRequest;

    expect($request->rules())->toEqual([
        'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
    ]);
});
