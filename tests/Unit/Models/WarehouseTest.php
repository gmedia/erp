<?php

use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('warehouses');

test('factory creates a valid warehouse', function () {
    $warehouse = Warehouse::factory()->create();

    assertDatabaseHas('warehouses', ['id' => $warehouse->id]);

    expect($warehouse->getAttributes())->toMatchArray([
        'branch_id' => $warehouse->branch_id,
        'code' => $warehouse->code,
        'name' => $warehouse->name,
    ]);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new Warehouse)->getFillable();

    expect($fillable)->toBe([
        'branch_id',
        'code',
        'name',
    ]);
});
