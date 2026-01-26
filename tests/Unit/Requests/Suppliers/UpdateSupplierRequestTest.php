<?php

use App\Http\Requests\Suppliers\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


uses()->group('suppliers');

test('authorize returns true', function () {
    $request = new UpdateSupplierRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $supplier = Supplier::factory()->create();

    $request = new UpdateSupplierRequest;
    $request->setRouteResolver(function () use ($supplier) {
        return new class($supplier) {
            public function __construct(public $supplier) {}
            public function parameter($key, $default = null) {
                return $this->supplier;
            }
        };
    });
 
    expect($request->rules())->toEqual([
        'name' => ['sometimes', 'string', 'max:255'],
        'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:suppliers,email,' . $supplier->id],
        'phone' => ['nullable', 'string', 'max:20'],
        'address' => ['sometimes', 'string'],
        'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        'category_id' => ['sometimes', 'required', 'integer', 'exists:supplier_categories,id'],
        'status' => ['sometimes', 'string', 'in:active,inactive'],
    ]);
});
