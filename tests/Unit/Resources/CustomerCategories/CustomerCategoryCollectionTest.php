<?php

use App\Http\Resources\CustomerCategories\CustomerCategoryCollection;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('customer-categories', 'resources');

test('to array transforms collection', function () {
    $categories = CustomerCategory::factory()->count(3)->create();
    
    $collection = new CustomerCategoryCollection($categories);
    $request = Request::create('/');
    
    $result = $collection->toArray($request);
    
    expect($result)->toHaveCount(3);
    expect($result[0]['name'])->toBe($categories[0]->name);
});
