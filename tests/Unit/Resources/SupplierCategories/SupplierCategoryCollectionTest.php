<?php

use App\Http\Resources\SupplierCategories\SupplierCategoryCollection;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('supplier-categories');

test('to array transforms collection', function () {
    $categories = SupplierCategory::factory()->count(3)->create();
    
    $collection = new SupplierCategoryCollection($categories);
    $request = Request::create('/');
    
    $result = $collection->toArray($request);
    
    expect($result)->toHaveCount(3);
    expect($result[0]['name'])->toBe($categories[0]->name);
});
