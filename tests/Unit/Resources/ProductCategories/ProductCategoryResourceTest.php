<?php

use App\Http\Resources\ProductCategories\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('product-categories');

test('to array returns correct structure', function () {
    $category = ProductCategory::factory()->create(['name' => 'Electronics']);
    
    $resource = new ProductCategoryResource($category);
    $request = Request::create('/');
    
    $result = $resource->toArray($request);
    
    expect($result)->toMatchArray([
        'id' => $category->id,
        'name' => 'Electronics',
    ]);
    
    expect($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});
