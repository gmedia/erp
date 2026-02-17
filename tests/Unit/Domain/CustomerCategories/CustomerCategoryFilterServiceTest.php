<?php

use App\Domain\CustomerCategories\CustomerCategoryFilterService;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('customer-categories');

test('apply search filters by name', function () {
    CustomerCategory::factory()->create(['name' => 'VIP']);
    CustomerCategory::factory()->create(['name' => 'Regular']);

    $service = new CustomerCategoryFilterService();
    $query = CustomerCategory::query();
    
    $service->applySearch($query, 'VIP', ['name']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('VIP');
});
