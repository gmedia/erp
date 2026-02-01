<?php

use App\Actions\Positions\IndexPositionsAction;
use App\Http\Requests\Positions\IndexPositionRequest;
use App\Models\Position;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('positions', 'actions');

test('execute returns paginated results', function () {
    Position::factory()->count(3)->create();

    $action = new IndexPositionsAction();
    $request = new IndexPositionRequest();
    
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    Position::factory()->create(['name' => 'Manager']);
    Position::factory()->create(['name' => 'Staff']);

    $action = new IndexPositionsAction();
    $request = new IndexPositionRequest(['search' => 'Manage']);
    
    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('Manager');
});

test('execute sorts results', function () {
    Position::factory()->create(['name' => 'A Pos']);
    Position::factory()->create(['name' => 'B Pos']);

    $action = new IndexPositionsAction();
    $request = new IndexPositionRequest([
        'sort_by' => 'name',
        'sort_direction' => 'desc'
    ]);
    
    $result = $action->execute($request);

    expect($result->first()->name)->toBe('B Pos');
});
