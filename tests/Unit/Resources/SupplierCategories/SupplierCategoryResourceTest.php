<?php

use App\Http\Resources\SupplierCategories\SupplierCategoryResource;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('supplier_categories');

test('toArray transforms supplier category correctly', function () {
    $category = SupplierCategory::factory()->create([
        'name' => 'Engineering',
        'created_at' => '2023-01-01 10:00:00',
        'updated_at' => '2023-01-02 11:00:00',
    ]);

    $resource = new SupplierCategoryResource($category);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKey('id', $category->id)
        ->and($result)->toHaveKey('name', 'Engineering')
        ->and($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});

test('toArray includes all required fields', function () {
    $category = SupplierCategory::factory()->create();

    $resource = new SupplierCategoryResource($category);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKeys(['id', 'name', 'created_at', 'updated_at'])
        ->and($result['id'])->toBe($category->id)
        ->and($result['name'])->toBe($category->name);
});

test('toArray handles null timestamps', function () {
    $category = SupplierCategory::factory()->create();
    $category->created_at = null;
    $category->updated_at = null;

    $resource = new SupplierCategoryResource($category);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result['created_at'])->toBeNull()
        ->and($result['updated_at'])->toBeNull();
});
