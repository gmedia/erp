<?php

use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);
uses(Position::class);

test('factory creates a valid position', function () {
    $position = Position::factory()->create();

    assertDatabaseHas('positions', ['id' => $position->id]);

    expect($position->getAttributes())->toMatchArray([
        'name'       => $position->name,
    ]);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new Position)->getFillable();

    expect($fillable)->toBe([
        'name',
    ]);
});
