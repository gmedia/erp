<?php

use App\Http\Requests\SupplierCategories\UpdateSupplierCategoryRequest;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('supplier-categories', 'requests');

test('authorize returns true', function () {
    $request = new UpdateSupplierCategoryRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $category = SupplierCategory::factory()->create();

    $request = Mockery::mock(UpdateSupplierCategoryRequest::class)->makePartial();
    
    $request->shouldReceive('route')
        ->with('supplier_category')
        ->andReturn($category);
        
    $request->shouldReceive('route')
        ->with('id')
        ->andReturn(null);

    expect($request->rules())->toEqual([
        'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:supplier_categories,name,' . $category->id],
    ]);
});
