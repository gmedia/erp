<?php

use App\Domain\SupplierCategories\SupplierCategoryFilterService;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('supplier-categories', 'domain');

test('apply search filters by name', function () {
    SupplierCategory::factory()->create(['name' => 'Material']);
    SupplierCategory::factory()->create(['name' => 'Service']);

    $service = new SupplierCategoryFilterService();
    $query = SupplierCategory::query();
    
    $service->applySearch($query, 'Material', ['name']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('Material');
});
