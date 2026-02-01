<?php

use App\Http\Requests\ProductCategories\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('product-categories', 'requests');

test('authorize returns true', function () {
    $request = new UpdateProductCategoryRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $category = ProductCategory::factory()->create();

    $request = Mockery::mock(UpdateProductCategoryRequest::class)->makePartial();
    
    $request->shouldReceive('route')
        ->with('product_category')
        ->andReturn($category);
        
    $request->shouldReceive('route')
        ->with('id')
        ->andReturn(null);

    expect($request->rules())->toEqual([
        'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:product_categories,name,' . $category->id],
        'description' => ['nullable', 'string'],
    ]);
});
