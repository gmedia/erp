<?php

use App\Actions\CustomerCategories\IndexCustomerCategoriesAction;
use App\Domain\CustomerCategories\CustomerCategoryFilterService;
use App\Http\Requests\CustomerCategories\IndexCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('customer_categories');

test('execute returns paginated customer categories', function () {
    $filterService = new CustomerCategoryFilterService;
    $action = new IndexCustomerCategoriesAction($filterService);

    CustomerCategory::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(IndexCustomerCategoryRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    $filterService = new CustomerCategoryFilterService;
    $action = new IndexCustomerCategoriesAction($filterService);

    CustomerCategory::factory()->create(['name' => 'VIP']);
    CustomerCategory::factory()->create(['name' => 'Regular']);

    // Mock request with search
    $request = Mockery::mock(IndexCustomerCategoryRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('vip');
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('VIP');
});

test('execute sorts results', function () {
    $filterService = new CustomerCategoryFilterService;
    $action = new IndexCustomerCategoriesAction($filterService);

    CustomerCategory::factory()->create(['name' => 'A Category']);
    CustomerCategory::factory()->create(['name' => 'B Category']);

    // Mock request with sort
    $request = Mockery::mock(IndexCustomerCategoryRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('name');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->first()->name)->toBe('B Category');
});
