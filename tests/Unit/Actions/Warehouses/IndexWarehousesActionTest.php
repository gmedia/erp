<?php

use App\Actions\Warehouses\IndexWarehousesAction;
use App\Http\Requests\Warehouses\IndexWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('warehouses');

test('execute returns paginated results', function () {
    Warehouse::factory()->count(3)->create();

    $action = new IndexWarehousesAction();
    $request = new IndexWarehouseRequest();

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    Warehouse::factory()->create(['name' => 'Main Warehouse']);
    Warehouse::factory()->create(['name' => 'Transit Warehouse']);

    $action = new IndexWarehousesAction();
    $request = new IndexWarehouseRequest(['search' => 'Main']);

    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('Main Warehouse');
});

test('execute sorts results', function () {
    Warehouse::factory()->create(['name' => 'A Warehouse']);
    Warehouse::factory()->create(['name' => 'B Warehouse']);

    $action = new IndexWarehousesAction();
    $request = new IndexWarehouseRequest([
        'sort_by' => 'name',
        'sort_direction' => 'desc'
    ]);

    $result = $action->execute($request);

    expect($result->first()->name)->toBe('B Warehouse');
});
