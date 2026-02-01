<?php

use App\Actions\Products\IndexProductsAction;
use App\Domain\Products\ProductFilterService;
use App\Http\Requests\Products\IndexProductRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('products');

test('execute returns paginated products without filters', function () {
    $filterService = Mockery::mock(ProductFilterService::class);
    $action = new IndexProductsAction($filterService);

    Product::factory()->count(5)->create();

    // Mock request
    $request = Mockery::mock(IndexProductRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('search')->andReturn(null);
    $request->shouldReceive('get')->with('category_id')->andReturn(null);
    $request->shouldReceive('get')->with('unit_id')->andReturn(null);
    $request->shouldReceive('get')->with('branch_id')->andReturn(null);
    $request->shouldReceive('get')->with('type')->andReturn(null);
    $request->shouldReceive('get')->with('status')->andReturn(null);
    $request->shouldReceive('get')->with('billing_model')->andReturn(null);
    $request->shouldReceive('get')->with('is_manufactured')->andReturn(null);
    $request->shouldReceive('get')->with('is_purchasable')->andReturn(null);
    $request->shouldReceive('get')->with('is_sellable')->andReturn(null);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    // Mock filter service calls
    $filterService->shouldReceive('applyAdvancedFilters')
        ->twice()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), Mockery::type('array'));

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc',
            ['id', 'code', 'name', 'type', 'category_id', 'unit_id', 'cost', 'selling_price', 'status', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class)
        ->and($result->total())->toBe(5);
});

test('execute applies search filter when provided', function () {
    $filterService = Mockery::mock(ProductFilterService::class);
    $action = new IndexProductsAction($filterService);

    Product::factory()->create(['name' => 'Widget']);

    // Mock request with search
    $request = Mockery::mock(IndexProductRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('widget');
    $request->shouldReceive('get')->with('type')->andReturn(null);
    $request->shouldReceive('get')->with('status')->andReturn(null);
    $request->shouldReceive('get')->with('billing_model')->andReturn(null);
    $request->shouldReceive('get')->with('is_manufactured')->andReturn(null);
    $request->shouldReceive('get')->with('is_purchasable')->andReturn(null);
    $request->shouldReceive('get')->with('is_sellable')->andReturn(null);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    // Mock filter service calls
    $filterService->shouldReceive('applySearch')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'widget',
            ['code', 'name', 'description']);

    $filterService->shouldReceive('applyAdvancedFilters')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), [
            'type' => null,
            'status' => null,
            'billing_model' => null,
            'is_manufactured' => null,
            'is_purchasable' => null,
            'is_sellable' => null,
        ]);

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc',
            ['id', 'code', 'name', 'type', 'category_id', 'unit_id', 'cost', 'selling_price', 'status', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
