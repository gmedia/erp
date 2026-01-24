<?php

use App\Http\Requests\Suppliers\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authorize returns true', function () {
    $request = new UpdateSupplierRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $supplier = Supplier::factory()->create();

    $request = new UpdateSupplierRequest;
    $request->setRouteResolver(function () use ($supplier) {
        return (object) ['supplier' => $supplier];
    });
 
    expect($request->rules())->toEqual([
        'name' => ['sometimes', 'string', 'max:255'],
        'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:suppliers,email,' . $supplier->id],
        'phone' => ['nullable', 'string', 'max:20'],
        'address' => ['sometimes', 'string'],
        'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        'category' => ['sometimes', 'string', 'in:electronics,furniture,stationery,services,other'],
        'status' => ['sometimes', 'string', 'in:active,inactive'],
    ]);
});
