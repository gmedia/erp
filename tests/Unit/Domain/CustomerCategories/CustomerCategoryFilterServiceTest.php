<?php

use App\Domain\CustomerCategories\CustomerCategoryFilterService;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('customer_categories');

test('applySearch adds where clause for search term', function () {
    $service = new CustomerCategoryFilterService;

    CustomerCategory::factory()->create(['name' => 'Engineering']);
    CustomerCategory::factory()->create(['name' => 'Marketing']);
    CustomerCategory::factory()->create(['name' => 'Sales']);

    $query = CustomerCategory::query();
    $service->applySearch($query, 'eng', ['name']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Engineering');
});

test('applySearch searches across multiple fields', function () {
    $service = new CustomerCategoryFilterService;

    CustomerCategory::factory()->create(['name' => 'Engineering']);
    CustomerCategory::factory()->create(['name' => 'Marketing']);

    $query = CustomerCategory::query();
    $service->applySearch($query, 'arket', ['name']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Marketing');
});

test('applySorting applies ascending sort when allowed', function () {
    $service = new CustomerCategoryFilterService;

    CustomerCategory::factory()->create(['name' => 'Z Category']);
    CustomerCategory::factory()->create(['name' => 'A Category']);

    $query = CustomerCategory::query();
    $service->applySorting($query, 'name', 'asc', ['id', 'name', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('A Category')
        ->and($results->last()->name)->toBe('Z Category');
});

test('applySorting applies descending sort when allowed', function () {
    $service = new CustomerCategoryFilterService;

    CustomerCategory::factory()->create(['name' => 'A Category']);
    CustomerCategory::factory()->create(['name' => 'Z Category']);

    $query = CustomerCategory::query();
    $service->applySorting($query, 'name', 'desc', ['id', 'name', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('Z Category')
        ->and($results->last()->name)->toBe('A Category');
});

test('applySorting does not apply sort when field not allowed', function () {
    $service = new CustomerCategoryFilterService;

    CustomerCategory::factory()->create(['name' => 'Test Category']);

    $query = CustomerCategory::query();
    $originalSql = $query->toSql();

    $service->applySorting($query, 'invalid_field', 'asc', ['id', 'name', 'created_at', 'updated_at']);

    // SQL should remain unchanged since invalid field
    expect($query->toSql())->toBe($originalSql);
});
