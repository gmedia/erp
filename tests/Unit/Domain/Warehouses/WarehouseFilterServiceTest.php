<?php

use App\Domain\Warehouses\WarehouseFilterService;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('warehouses');

test('apply search filters by name or code', function () {
    Warehouse::factory()->create(['name' => 'Main Warehouse', 'code' => 'WH-MAIN']);
    Warehouse::factory()->create(['name' => 'Transit Warehouse', 'code' => 'WH-TRN']);

    $service = new WarehouseFilterService();
    $query = Warehouse::query();

    $service->applySearch($query, 'WH-MAIN', ['code', 'name']);

    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('Main Warehouse');
});

test('apply advanced filters by branch_id', function () {
    $warehouse1 = Warehouse::factory()->create();
    Warehouse::factory()->count(2)->create();

    $service = new WarehouseFilterService();
    $query = Warehouse::query();

    $service->applyAdvancedFilters($query, ['branch_id' => $warehouse1->branch_id]);

    expect($query->count())->toBe(1)
        ->and($query->first()->id)->toBe($warehouse1->id);
});
