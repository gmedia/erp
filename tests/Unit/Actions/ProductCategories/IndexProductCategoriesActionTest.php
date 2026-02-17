<?php

use App\Actions\ProductCategories\IndexProductCategoriesAction;
use App\Http\Requests\ProductCategories\IndexProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('product-categories');

test('execute returns paginated results', function () {
    ProductCategory::factory()->count(3)->create();

    $action = new IndexProductCategoriesAction();
    $request = new IndexProductCategoryRequest();
    
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    ProductCategory::factory()->create(['name' => 'Electronics']);
    ProductCategory::factory()->create(['name' => 'Furniture']);

    $action = new IndexProductCategoriesAction();
    $request = new IndexProductCategoryRequest(['search' => 'Elect']);
    
    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('Electronics');
});
