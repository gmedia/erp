<?php

use App\Http\Requests\Employees\ImportEmployeeRequest;

uses()->group('employees');

test('import employee request authorizes access', function () {
    $request = new ImportEmployeeRequest;

    expect($request->authorize())->toBeTrue();
});

test('import employee request returns correct validation rules', function () {
    $request = new ImportEmployeeRequest;

    expect($request->rules())->toEqual([
        'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
    ]);
});
