<?php

use App\Actions\SupplierCategories\IndexSupplierCategoriesAction;
use App\Http\Requests\SupplierCategories\IndexSupplierCategoryRequest;
use App\Models\SupplierCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('supplier-categories');

test('execute returns paginated results', function () {
    SupplierCategory::factory()->count(3)->create();

    $action = new IndexSupplierCategoriesAction();
    $request = new IndexSupplierCategoryRequest();
    
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    SupplierCategory::factory()->create(['name' => 'Raw Material']);
    SupplierCategory::factory()->create(['name' => 'Service']);

    $action = new IndexSupplierCategoriesAction();
    $request = new IndexSupplierCategoryRequest(['search' => 'Raw']);
    
    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('Raw Material');
});
