<?php

use App\Domain\Warehouses\WarehouseFilterService;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('warehouses');

test('apply search filters by name', function () {
    Warehouse::factory()->create(['name' => 'Main Warehouse']);
    Warehouse::factory()->create(['name' => 'Transit Warehouse']);

    $service = new WarehouseFilterService();
    $query = Warehouse::query();

    $service->applySearch($query, 'Main', ['name']);

    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('Main Warehouse');
});
