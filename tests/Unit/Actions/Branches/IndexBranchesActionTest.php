<?php

use App\Actions\Branches\IndexBranchesAction;
use App\Http\Requests\Branches\IndexBranchRequest;
use App\Models\Branch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('branches');

test('execute returns paginated results', function () {
    Branch::factory()->count(3)->create();

    $action = new IndexBranchesAction();
    $request = new IndexBranchRequest();
    
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    Branch::factory()->create(['name' => 'HQ']);
    Branch::factory()->create(['name' => 'Branch 2']);

    $action = new IndexBranchesAction();
    $request = new IndexBranchRequest(['search' => 'HQ']);
    
    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('HQ');
});
