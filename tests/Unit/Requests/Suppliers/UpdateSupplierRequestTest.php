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
        'name' => 'sometimes|required|string|max:255',
        'email' => [
            'sometimes',
            'required',
            'email',
            \Illuminate\Validation\Rule::unique('suppliers', 'email')->ignore($supplier->id),
        ],
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'branch_id' => 'sometimes|required|exists:branches,id',
        'category_id' => 'sometimes|required|exists:supplier_categories,id',
        'status' => 'sometimes|required|in:active,inactive',
    ]);
});
