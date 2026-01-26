<?php

use App\Actions\CustomerCategories\ExportCustomerCategoriesAction;
use App\Domain\CustomerCategories\CustomerCategoryFilterService;
use App\Http\Requests\CustomerCategories\ExportCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('customer_categories');

test('execute exports customer categories and returns file info', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = new CustomerCategoryFilterService;
    $action = new ExportCustomerCategoriesAction($filterService);

    CustomerCategory::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(ExportCustomerCategoryRequest::class);
    $request->shouldReceive('validated')->andReturn([]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);

    $data = $result->getData(true);
    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toContain('customer_categories_export_')
        ->and($data['filename'])->toContain('.xlsx');
});

test('execute exports with search filter', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = new CustomerCategoryFilterService;
    $action = new ExportCustomerCategoriesAction($filterService);

    CustomerCategory::factory()->create(['name' => 'Engineering']);
    CustomerCategory::factory()->create(['name' => 'Marketing']);

    // Mock request with search
    $request = Mockery::mock(ExportCustomerCategoryRequest::class);
    $request->shouldReceive('validated')->andReturn(['search' => 'eng']);
    $request->shouldReceive('filled')->with('search')->andReturn(true);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});

test('execute exports with custom sort parameters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = new CustomerCategoryFilterService;
    $action = new ExportCustomerCategoriesAction($filterService);

    CustomerCategory::factory()->count(2)->create();

    // Mock request with custom sort
    $request = Mockery::mock(ExportCustomerCategoryRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'sort_by' => 'name',
        'sort_direction' => 'asc',
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});

test('execute filters out null values from filters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = new CustomerCategoryFilterService;
    $action = new ExportCustomerCategoriesAction($filterService);

    CustomerCategory::factory()->count(2)->create();

    // Mock request with some null values
    $request = Mockery::mock(ExportCustomerCategoryRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => 'test',
        'sort_by' => null,
        'sort_direction' => null,
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(true);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
