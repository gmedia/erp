<?php

use App\Actions\FiscalYears\IndexFiscalYearsAction;
use App\Domain\FiscalYears\FiscalYearFilterService;
use App\Http\Requests\FiscalYears\IndexFiscalYearRequest;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('fiscal-years');

test('execute returns paginated fiscal years', function () {
    $filterService = Mockery::mock(FiscalYearFilterService::class);
    $action = new IndexFiscalYearsAction($filterService);

    FiscalYear::factory()->count(5)->create();

    $request = Mockery::mock(IndexFiscalYearRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('search')->andReturn(null);
    $request->shouldReceive('get')->with('status')->andReturn(null);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    $filterService->shouldReceive('applyAdvancedFilters')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), Mockery::type('array'));

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc',
            ['id', 'name', 'start_date', 'end_date', 'status', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class)
        ->and($result->total())->toBe(5);
});

test('execute applies search filter', function () {
    $filterService = Mockery::mock(FiscalYearFilterService::class);
    $action = new IndexFiscalYearsAction($filterService);

    $request = Mockery::mock(IndexFiscalYearRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('2025');
    $request->shouldReceive('get')->with('status')->andReturn(null);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    $filterService->shouldReceive('applySearch')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), '2025', ['name']);

    $filterService->shouldReceive('applySorting')
        ->once();

    $action->execute($request);
});
