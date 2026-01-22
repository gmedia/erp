<?php

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

test('factory creates a valid branch', function () {
    $branch = Branch::factory()->create();

    assertDatabaseHas('branches', ['id' => $branch->id]);

    expect($branch->getAttributes())->toMatchArray([
        'name' => $branch->name,
    ]);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new Branch)->getFillable();

    expect($fillable)->toBe([
        'name',
    ]);
});
