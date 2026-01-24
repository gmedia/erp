<?php

use App\Domain\Suppliers\SupplierFilterService;
use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('suppliers');

test('applySearch filters by name', function () {
    $service = new SupplierFilterService;
    Supplier::factory()->create(['name' => 'Alpha Corp']);
    Supplier::factory()->create(['name' => 'Beta Ltd']);

    $query = Supplier::query();
    $service->applySearch($query, 'Alpha', ['name']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('Alpha Corp');
});

test('applyAdvancedFilters filters by branch', function () {
    $service = new SupplierFilterService;
    $branch1 = Branch::factory()->create();
    $branch2 = Branch::factory()->create();

    Supplier::factory()->create(['branch_id' => $branch1->id]);
    Supplier::factory()->create(['branch_id' => $branch2->id]);

    $query = Supplier::query();
    $service->applyAdvancedFilters($query, ['branch_id' => $branch1->id]);

    expect($query->count())->toBe(1)
        ->and($query->first()->branch_id)->toBe($branch1->id);
});

test('applyAdvancedFilters filters by category', function () {
    $service = new SupplierFilterService;
    Supplier::factory()->create(['category' => 'electronics']);
    Supplier::factory()->create(['category' => 'services']);

    $query = Supplier::query();
    $service->applyAdvancedFilters($query, ['category' => 'electronics']);

    expect($query->count())->toBe(1)
        ->and($query->first()->category)->toBe('electronics');
});

test('applyAdvancedFilters filters by status', function () {
    $service = new SupplierFilterService;
    Supplier::factory()->create(['status' => 'active']);
    Supplier::factory()->create(['status' => 'inactive']);

    $query = Supplier::query();
    $service->applyAdvancedFilters($query, ['status' => 'active']);

    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe('active');
});
