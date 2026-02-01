<?php

use App\Actions\CoaVersions\IndexCoaVersionsAction;
use App\Domain\CoaVersions\CoaVersionFilterService;
use App\Http\Requests\CoaVersions\IndexCoaVersionRequest;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('coa-versions');

test('execute returns paginated coa versions', function () {
    $filterService = Mockery::mock(CoaVersionFilterService::class);
    $filterService->shouldReceive('applyAdvancedFilters')->once();
    $filterService->shouldReceive('applySorting')->once();

    $action = new IndexCoaVersionsAction($filterService);

    CoaVersion::factory()->count(5)->create();

    $request = Mockery::mock(IndexCoaVersionRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('search')->andReturn(null);
    $request->shouldReceive('get')->with('status')->andReturn(null);
    $request->shouldReceive('get')->with('fiscal_year_id')->andReturn(null);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('validated')->andReturn([]);

    $results = $action->execute($request);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->total())->toBe(5);
});
