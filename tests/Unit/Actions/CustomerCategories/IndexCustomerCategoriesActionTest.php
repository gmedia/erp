<?php

use App\Actions\CustomerCategories\IndexCustomerCategoriesAction;
use App\Http\Requests\CustomerCategories\IndexCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('customer-categories');

test('execute returns paginated results', function () {
    CustomerCategory::factory()->count(3)->create();

    $action = new IndexCustomerCategoriesAction();
    $request = new IndexCustomerCategoryRequest();
    
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    CustomerCategory::factory()->create(['name' => 'VIP']);
    CustomerCategory::factory()->create(['name' => 'Regular']);

    $action = new IndexCustomerCategoriesAction();
    $request = new IndexCustomerCategoryRequest(['search' => 'VIP']);
    
    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('VIP');
});
