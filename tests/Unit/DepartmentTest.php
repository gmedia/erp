<?php

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);
uses(Department::class);

test('factory creates a valid department', function () {
    $department = Department::factory()->create();

    assertDatabaseHas('departments', ['id' => $department->id]);

    expect($department->getAttributes())->toMatchArray([
        'name' => $department->name,
    ]);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new Department)->getFillable();

    expect($fillable)->toBe([
        'name',
    ]);
});
