<?php

use App\Domain\Products\ProductFilterService;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->filterService = new ProductFilterService();
});

test('it filters by search name', function () {
    Product::factory()->create(['name' => 'Widget A']);
    Product::factory()->create(['name' => 'Gadget B']);

    $query = Product::query();
    $this->filterService->applySearch($query, 'Widget', ['name']);

    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('Widget A');
});

test('it filters by search code', function () {
    Product::factory()->create(['code' => 'CODE-123']);
    Product::factory()->create(['code' => 'CODE-456']);

    $query = Product::query();
    $this->filterService->applySearch($query, '123', ['code']);

    expect($query->count())->toBe(1)
        ->and($query->first()->code)->toBe('CODE-123');
});

test('it filters by category', function () {
    $cat1 = ProductCategory::factory()->create();
    $cat2 = ProductCategory::factory()->create();
    
    Product::factory()->create(['category_id' => $cat1->id]);
    Product::factory()->create(['category_id' => $cat2->id]);

    $query = Product::query();
    $this->filterService->applyAdvancedFilters($query, ['category_id' => $cat1->id]);

    expect($query->count())->toBe(1)
        ->and($query->first()->category_id)->toBe($cat1->id);
});

test('it filters by status', function () {
    Product::factory()->create(['status' => 'active']);
    Product::factory()->create(['status' => 'inactive']);

    $query = Product::query();
    $this->filterService->applyAdvancedFilters($query, ['status' => 'active']);

    expect($query->count())->toBe(1);
});

test('it filters by type', function () {
    Product::factory()->create(['type' => 'service']);
    Product::factory()->create(['type' => 'finished_good']);

    $query = Product::query();
    $this->filterService->applyAdvancedFilters($query, ['type' => 'service']);

    expect($query->count())->toBe(1);
});

test('it filters by boolean flags', function () {
    Product::factory()->create(['is_manufactured' => true]);
    Product::factory()->create(['is_manufactured' => false]);

    $query = Product::query();
    $this->filterService->applyAdvancedFilters($query, ['is_manufactured' => true]);

    expect($query->count())->toBe(1);
});
