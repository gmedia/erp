<?php

use App\Domain\SupplierCategories\SupplierCategoryFilterService;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('supplier_categories');

test('applySearch adds where clause for search term', function () {
    $service = new SupplierCategoryFilterService;

    SupplierCategory::factory()->create(['name' => 'Engineering']);
    SupplierCategory::factory()->create(['name' => 'Marketing']);
    SupplierCategory::factory()->create(['name' => 'Sales']);

    $query = SupplierCategory::query();
    $service->applySearch($query, 'eng', ['name']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Engineering');
});

test('applySearch searches across multiple fields', function () {
    $service = new SupplierCategoryFilterService;

    SupplierCategory::factory()->create(['name' => 'Engineering']);
    SupplierCategory::factory()->create(['name' => 'Marketing']);

    $query = SupplierCategory::query();
    $service->applySearch($query, 'arket', ['name']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Marketing');
});

test('applySorting applies ascending sort when allowed', function () {
    $service = new SupplierCategoryFilterService;

    SupplierCategory::factory()->create(['name' => 'Z Category']);
    SupplierCategory::factory()->create(['name' => 'A Category']);

    $query = SupplierCategory::query();
    $service->applySorting($query, 'name', 'asc', ['id', 'name', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('A Category')
        ->and($results->last()->name)->toBe('Z Category');
});

test('applySorting applies descending sort when allowed', function () {
    $service = new SupplierCategoryFilterService;

    SupplierCategory::factory()->create(['name' => 'A Category']);
    SupplierCategory::factory()->create(['name' => 'Z Category']);

    $query = SupplierCategory::query();
    $service->applySorting($query, 'name', 'desc', ['id', 'name', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('Z Category')
        ->and($results->last()->name)->toBe('A Category');
});

test('applySorting does not apply sort when field not allowed', function () {
    $service = new SupplierCategoryFilterService;

    SupplierCategory::factory()->create(['name' => 'Test Category']);

    $query = SupplierCategory::query();
    $originalSql = $query->toSql();

    $service->applySorting($query, 'invalid_field', 'asc', ['id', 'name', 'created_at', 'updated_at']);

    // SQL should remain unchanged since invalid field
    expect($query->toSql())->toBe($originalSql);
});
