<?php

use App\Models\Department;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

describe('CRUD Model Tests', function () {

    dataset('crud_models', [
        [Department::class, 'departments', 'Department'],
        [Position::class, 'positions', 'Position'],
    ]);

    test('{2} factory creates a valid item', function ($model, $table, $name) {
        $item = $model::factory()->create();

        assertDatabaseHas($table, ['id' => $item->id]);

        expect($item->getAttributes())->toMatchArray([
            'name' => $item->name,
        ]);
    })->with('crud_models');

    test('{2} fillable attributes are defined correctly', function ($model, $table, $name) {
        $fillable = (new $model)->getFillable();

        expect($fillable)->toBe([
            'name',
        ]);
    })->with('crud_models');

});
