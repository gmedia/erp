<?php

use App\Http\Resources\CustomerCategories\CustomerCategoryResource;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('customer_categories');

test('toArray transforms customer category correctly', function () {
    $category = CustomerCategory::factory()->create([
        'name' => 'Engineering',
        'created_at' => '2023-01-01 10:00:00',
        'updated_at' => '2023-01-02 11:00:00',
    ]);

    $resource = new CustomerCategoryResource($category);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKey('id', $category->id)
        ->and($result)->toHaveKey('name', 'Engineering')
        ->and($result['created_at'])->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($result['updated_at'])->toBeInstanceOf(\Carbon\Carbon::class);
});

test('toArray includes all required fields', function () {
    $category = CustomerCategory::factory()->create();

    $resource = new CustomerCategoryResource($category);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKeys(['id', 'name', 'created_at', 'updated_at'])
        ->and($result['id'])->toBe($category->id)
        ->and($result['name'])->toBe($category->name);
});

test('toArray handles null timestamps', function () {
    $category = CustomerCategory::factory()->create();
    $category->created_at = null;
    $category->updated_at = null;

    $resource = new CustomerCategoryResource($category);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result['created_at'])->toBeNull()
        ->and($result['updated_at'])->toBeNull();
});
