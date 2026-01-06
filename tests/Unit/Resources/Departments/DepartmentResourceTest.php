<?php

use App\Http\Resources\Departments\DepartmentResource;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('toArray transforms department correctly', function () {
    $department = Department::factory()->create([
        'name' => 'Engineering',
        'created_at' => '2023-01-01 10:00:00',
        'updated_at' => '2023-01-02 11:00:00',
    ]);

    $resource = new DepartmentResource($department);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKey('id', $department->id)
        ->and($result)->toHaveKey('name', 'Engineering')
        ->and($result['created_at'])->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($result['updated_at'])->toBeInstanceOf(\Carbon\Carbon::class);
});

test('toArray includes all required fields', function () {
    $department = Department::factory()->create();

    $resource = new DepartmentResource($department);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKeys(['id', 'name', 'created_at', 'updated_at'])
        ->and($result['id'])->toBe($department->id)
        ->and($result['name'])->toBe($department->name);
});

test('toArray handles null timestamps', function () {
    $department = Department::factory()->create();
    $department->created_at = null;
    $department->updated_at = null;

    $resource = new DepartmentResource($department);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result['created_at'])->toBeNull()
        ->and($result['updated_at'])->toBeNull();
});
