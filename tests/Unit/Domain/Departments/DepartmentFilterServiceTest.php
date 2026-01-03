<?php

use App\Domain\Departments\DepartmentFilterService;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('applySearch adds where clause for search term', function () {
    $service = new DepartmentFilterService();

    Department::factory()->create(['name' => 'Engineering']);
    Department::factory()->create(['name' => 'Marketing']);
    Department::factory()->create(['name' => 'Sales']);

    $query = Department::query();
    $service->applySearch($query, 'eng', ['name']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Engineering');
});

test('applySearch searches across multiple fields', function () {
    $service = new DepartmentFilterService();

    Department::factory()->create(['name' => 'Engineering', 'description' => 'Tech department']);
    Department::factory()->create(['name' => 'Marketing', 'description' => 'Sales department']);

    $query = Department::query();
    $service->applySearch($query, 'department', ['name', 'description']);

    $results = $query->get();

    expect($results)->toHaveCount(2);
});

test('applySorting applies ascending sort when allowed', function () {
    $service = new DepartmentFilterService();

    Department::factory()->create(['name' => 'Z Department']);
    Department::factory()->create(['name' => 'A Department']);

    $query = Department::query();
    $service->applySorting($query, 'name', 'asc', ['id', 'name', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('A Department')
        ->and($results->last()->name)->toBe('Z Department');
});

test('applySorting applies descending sort when allowed', function () {
    $service = new DepartmentFilterService();

    Department::factory()->create(['name' => 'A Department']);
    Department::factory()->create(['name' => 'Z Department']);

    $query = Department::query();
    $service->applySorting($query, 'name', 'desc', ['id', 'name', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('Z Department')
        ->and($results->last()->name)->toBe('A Department');
});

test('applySorting does not apply sort when field not allowed', function () {
    $service = new DepartmentFilterService();

    Department::factory()->create(['name' => 'Test Department']);

    $query = Department::query();
    $originalSql = $query->toSql();

    $service->applySorting($query, 'invalid_field', 'asc', ['id', 'name', 'created_at', 'updated_at']);

    // SQL should remain unchanged since invalid field
    expect($query->toSql())->toBe($originalSql);
});
