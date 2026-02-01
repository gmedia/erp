<?php

use App\Actions\Units\IndexUnitsAction;
use App\Http\Requests\Units\IndexUnitRequest;
use App\Models\Unit;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('units', 'actions');

test('execute returns paginated results', function () {
    Unit::factory()->count(3)->create();

    $action = new IndexUnitsAction();
    $request = new IndexUnitRequest();
    
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    Unit::factory()->create(['name' => 'Kilogram']);
    Unit::factory()->create(['name' => 'Meter']);

    $action = new IndexUnitsAction();
    $request = new IndexUnitRequest(['search' => 'Kilo']);
    
    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('Kilogram');
});
