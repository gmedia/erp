<?php

use App\Domain\Products\ProductFilterService;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('products');

test('applyAdvancedFilters applies category filter', function () {
    $service = new ProductFilterService;
    $cat = ProductCategory::factory()->create();

    Product::factory()->create(['product_category_id' => $cat->id]);
    Product::factory()->create();

    $query = Product::query();
    $service->applyAdvancedFilters($query, ['product_category_id' => $cat->id]);

    expect($query->count())->toBe(1);
});

test('applyAdvancedFilters applies unit filter', function () {
    $service = new ProductFilterService;
    $unit = Unit::factory()->create();

    Product::factory()->create(['unit_id' => $unit->id]);
    Product::factory()->create();

    $query = Product::query();
    $service->applyAdvancedFilters($query, ['unit_id' => $unit->id]);

    expect($query->count())->toBe(1);
});

test('applyAdvancedFilters applies branch filter', function () {
    $service = new ProductFilterService;
    $branch = Branch::factory()->create();

    Product::factory()->create(['branch_id' => $branch->id]);
    Product::factory()->create();

    $query = Product::query();
    $service->applyAdvancedFilters($query, ['branch_id' => $branch->id]);

    expect($query->count())->toBe(1);
});

test('applyAdvancedFilters applies type filter', function () {
    $service = new ProductFilterService;

    Product::factory()->create(['type' => 'finished_good']);
    Product::factory()->create(['type' => 'raw_material']);

    $query = Product::query();
    $service->applyAdvancedFilters($query, ['type' => 'finished_good']);

    expect($query->count())->toBe(1);
});

test('applyAdvancedFilters applies status filter', function () {
    $service = new ProductFilterService;

    Product::factory()->create(['status' => 'active']);
    Product::factory()->create(['status' => 'inactive']);

    $query = Product::query();
    $service->applyAdvancedFilters($query, ['status' => 'active']);

    expect($query->count())->toBe(1);
});

test('applyAdvancedFilters applies billing model filter', function () {
    $service = new ProductFilterService;

    Product::factory()->create(['billing_model' => 'subscription']);
    Product::factory()->create(['billing_model' => 'one_time']);

    $query = Product::query();
    $service->applyAdvancedFilters($query, ['billing_model' => 'subscription']);

    expect($query->count())->toBe(1);
});
