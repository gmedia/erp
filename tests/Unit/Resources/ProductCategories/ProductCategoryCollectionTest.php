<?php

use App\Http\Resources\ProductCategories\ProductCategoryCollection;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('product-categories');

test('to array transforms collection', function () {
    $categories = ProductCategory::factory()->count(3)->create();
    
    $collection = new ProductCategoryCollection($categories);
    $request = Request::create('/');
    
    $result = $collection->toArray($request);
    
    expect($result)->toHaveCount(3);
    expect($result[0]['name'])->toBe($categories[0]->name);
});
