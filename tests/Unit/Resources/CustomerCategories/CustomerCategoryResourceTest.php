<?php

use App\Http\Resources\CustomerCategories\CustomerCategoryResource;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('customer-categories', 'resources');

test('to array returns correct structure', function () {
    $category = CustomerCategory::factory()->create(['name' => 'VIP']);
    
    $resource = new CustomerCategoryResource($category);
    $request = Request::create('/');
    
    $result = $resource->toArray($request);
    
    expect($result)->toMatchArray([
        'id' => $category->id,
        'name' => 'VIP',
    ]);
    
    expect($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});
