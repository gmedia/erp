<?php

use App\Actions\Suppliers\IndexSuppliersAction;
use App\Domain\Suppliers\SupplierFilterService;
use App\Http\Requests\Suppliers\IndexSupplierRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('suppliers');

test('execute calls filter service with correct parameters', function () {
    $filterService = Mockery::mock(SupplierFilterService::class);
    $action = new IndexSuppliersAction($filterService);

    $request = Mockery::mock(IndexSupplierRequest::class);
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('test');
    
    // Advanced filters
    $request->shouldReceive('get')->with('branch_id')->andReturn(null);
    $request->shouldReceive('get')->with('category_id')->andReturn(null);
    $request->shouldReceive('get')->with('status')->andReturn(null);

    // Sorting
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');

    // Expect calls to filter service
    $filterService->shouldReceive('applySearch')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'test', ['name', 'email', 'phone', 'address']);

    $filterService->shouldReceive('applyAdvancedFilters')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), [
            'branch_id' => null,
            'category_id' => null,
            'status' => null,
        ]);

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc', 
            ['id', 'name', 'email', 'phone', 'address', 'branch_id', 'category_id', 'status', 'created_at', 'updated_at']);

    // Mock pagination
    $builder = Mockery::mock('Illuminate\Database\Eloquent\Builder');
    $builder->shouldReceive('with')->with(['branch', 'category'])->andReturnSelf();
    $builder->shouldReceive('paginate')->with(15, ['*'], 'page', 1)->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15));

    // We can't easily mock the static query() call on the model without alias mocking, 
    // so we'll test the service interaction via a real instance or partial mock if needed.
    // However, since IndexSuppliersAction uses Supplier::query(), it's hard to mock that without Facades.
    // Instead, let's just create real filter service interaction or rely on the fact that we passed the mock to the constructor.
    // But wait, the Action calls methods on the service.
    
    // The issue is $query sent to service methods is created inside execute(): Supplier::query()
    // To test this properly without a real DB hit or complex mocking, we might just use real objects for what we can.
    
    // Actually, let's keep it simple and just verify the filter service methods are called. 
    // But since we can't inject the query builder into the action, we can't easily verify the *arguments* passed to it match our expectations 
    // unless we spy on the service and the service is injected.
    
    // The previous test for Customer used Mockery on the service, which is good. 
    // However, the query builder passed validation 'Mockery::type(...)' will match any Builder.
    
    // Let's just run it and see. The main thing is that we instantiated Action with the Mock Service.
    // But the Action will create a REAL Builder from Supplier::query().
    // That real builder will be passed to the mock service. That is fine.
    
    $action->execute($request);
});
