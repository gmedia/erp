<?php

use App\Domain\ProductCategories\ProductCategoryFilterService;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('product-categories', 'domain');

test('apply search filters by name', function () {
    ProductCategory::factory()->create(['name' => 'Electronics']);
    ProductCategory::factory()->create(['name' => 'Furniture']);

    $service = new ProductCategoryFilterService();
    $query = ProductCategory::query();
    
    $service->applySearch($query, 'Electro', ['name']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('Electronics');
});
