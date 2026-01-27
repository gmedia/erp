<?php

use App\Http\Resources\Departments\DepartmentCollection;
use App\Http\Resources\Departments\DepartmentResource;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('departments');

test('collects property is set correctly', function () {
    $collection = new DepartmentCollection([]);

    expect($collection->collects)->toBe(\App\Http\Resources\SimpleCrudResource::class);
});

test('collection transforms multiple departments correctly', function () {
    $departments = Department::factory()->count(3)->create();

    $collection = new DepartmentCollection($departments);
    $request = new Request;

    $result = $collection->toArray($request);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(3);

    foreach ($result as $index => $item) {
        expect($item)->toHaveKeys(['id', 'name', 'created_at', 'updated_at'])
            ->and($item['id'])->toBe($departments[$index]->id)
            ->and($item['name'])->toBe($departments[$index]->name)
            ->and($item['created_at'])->toBeString()
            ->and($item['updated_at'])->toBeString();
    }
});

test('collection returns empty array when no departments', function () {
    $collection = new DepartmentCollection(collect());
    $request = new Request;

    $result = $collection->toArray($request);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(0);
});
