<?php

use App\Actions\SupplierCategories\IndexSupplierCategoriesAction;
use App\Domain\SupplierCategories\SupplierCategoryFilterService;
use App\Http\Requests\SupplierCategories\IndexSupplierCategoryRequest;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('supplier_categories');

test('execute returns paginated supplier categories', function () {
    $filterService = new SupplierCategoryFilterService;
    $action = new IndexSupplierCategoriesAction($filterService);

    SupplierCategory::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(IndexSupplierCategoryRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    $filterService = new SupplierCategoryFilterService;
    $action = new IndexSupplierCategoriesAction($filterService);

    SupplierCategory::factory()->create(['name' => 'Tech Supplies']);
    SupplierCategory::factory()->create(['name' => 'Office Supplies']);

    // Mock request with search
    $request = Mockery::mock(IndexSupplierCategoryRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('tech');
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('Tech Supplies');
});

test('execute sorts results', function () {
    $filterService = new SupplierCategoryFilterService;
    $action = new IndexSupplierCategoriesAction($filterService);

    SupplierCategory::factory()->create(['name' => 'A Supply']);
    SupplierCategory::factory()->create(['name' => 'B Supply']);

    // Mock request with sort
    $request = Mockery::mock(IndexSupplierCategoryRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('name');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->first()->name)->toBe('B Supply');
});
