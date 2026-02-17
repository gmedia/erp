<?php

use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('units');

test('factory creates a valid unit', function () {
    $unit = Unit::factory()->create();

    assertDatabaseHas('units', ['id' => $unit->id]);

    expect($unit->getAttributes())->toMatchArray([
        'name' => $unit->name,
        'symbol' => $unit->symbol,
    ]);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new Unit)->getFillable();

    expect($fillable)->toBe(['name', 'symbol']);
});
