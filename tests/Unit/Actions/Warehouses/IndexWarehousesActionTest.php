<?php

use App\Actions\Warehouses\IndexWarehousesAction;
use App\Domain\Warehouses\WarehouseFilterService;
use App\Http\Requests\Warehouses\IndexWarehouseRequest;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('warehouses');

test('execute returns paginated results', function () {
    Warehouse::factory()->count(3)->create();

    $action = new IndexWarehousesAction(new WarehouseFilterService);
    $request = new IndexWarehouseRequest;

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    Warehouse::factory()->create(['name' => 'Main Warehouse']);
    Warehouse::factory()->create(['name' => 'Transit Warehouse']);

    $action = new IndexWarehousesAction(new WarehouseFilterService);
    $request = new IndexWarehouseRequest(['search' => 'Main']);

    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('Main Warehouse');
});

test('execute sorts results', function () {
    Warehouse::factory()->create(['name' => 'A Warehouse']);
    Warehouse::factory()->create(['name' => 'B Warehouse']);

    $action = new IndexWarehousesAction(new WarehouseFilterService);
    $request = new IndexWarehouseRequest([
        'sort_by' => 'name',
        'sort_direction' => 'desc',
    ]);

    $result = $action->execute($request);

    expect($result->first()->name)->toBe('B Warehouse');
});

test('execute filters by branch_id', function () {
    $warehouse1 = Warehouse::factory()->create();
    Warehouse::factory()->count(2)->create();

    $action = new IndexWarehousesAction(new WarehouseFilterService);
    $request = new IndexWarehouseRequest(['branch_id' => $warehouse1->branch_id]);

    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->id)->toBe($warehouse1->id);
});

test('execute sorts by branch name', function () {
    $branchA = Branch::factory()->create(['name' => 'A Branch']);
    $branchB = Branch::factory()->create(['name' => 'B Branch']);

    Warehouse::factory()->create(['branch_id' => $branchB->id, 'name' => 'W1']);
    Warehouse::factory()->create(['branch_id' => $branchA->id, 'name' => 'W2']);

    $action = new IndexWarehousesAction(new WarehouseFilterService);
    $request = new IndexWarehouseRequest(['sort_by' => 'branch', 'sort_direction' => 'asc']);

    $result = $action->execute($request);

    expect($result->first()->branch_id)->toBe($branchA->id);
});
