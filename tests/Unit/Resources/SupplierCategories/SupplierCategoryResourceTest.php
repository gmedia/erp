<?php

use App\Http\Resources\SupplierCategories\SupplierCategoryResource;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('supplier-categories', 'resources');

test('to array returns correct structure', function () {
    $category = SupplierCategory::factory()->create(['name' => 'Raw Materials']);
    
    $resource = new SupplierCategoryResource($category);
    $request = Request::create('/');
    
    $result = $resource->toArray($request);
    
    expect($result)->toMatchArray([
        'id' => $category->id,
        'name' => 'Raw Materials',
    ]);
    
    expect($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});
