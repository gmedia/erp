<?php

use App\Http\Requests\CustomerCategories\UpdateCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('customer-categories');

test('authorize returns true', function () {
    $request = new UpdateCustomerCategoryRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $category = CustomerCategory::factory()->create();

    $request = Mockery::mock(UpdateCustomerCategoryRequest::class)->makePartial();
    
    $request->shouldReceive('route')
        ->with('customer_category')
        ->andReturn($category);
        
    $request->shouldReceive('route')
        ->with('id')
        ->andReturn(null);

    expect($request->rules())->toEqual([
        'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:customer_categories,name,' . $category->id],
    ]);
});
