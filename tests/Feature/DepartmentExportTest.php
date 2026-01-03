<?php

use App\Exports\DepartmentExport;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('department export query applies search filter', function () {
    Department::factory()->create(['name' => 'Engineering']);
    Department::factory()->create(['name' => 'Marketing']);

    $export = new DepartmentExport(['search' => 'eng']);

    $query = $export->query();

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Engineering');
});

test('department export query applies name filter', function () {
    Department::factory()->create(['name' => 'Engineering']);
    Department::factory()->create(['name' => 'Marketing']);

    $export = new DepartmentExport(['name' => 'Engineering']);

    $query = $export->query();

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Engineering');
});

test('department export query applies sorting', function () {
    Department::factory()->create(['name' => 'Z Department']);
    Department::factory()->create(['name' => 'A Department']);

    $export = new DepartmentExport(['sort_by' => 'name', 'sort_direction' => 'asc']);

    $query = $export->query();

    $results = $query->get();

    expect($results->first()->name)->toBe('A Department')
        ->and($results->last()->name)->toBe('Z Department');
});

test('department export headings are correct', function () {
    $export = new DepartmentExport([]);

    $headings = $export->headings();

    expect($headings)->toBe([
        'ID',
        'Name',
        'Created At',
        'Updated At',
    ]);
});

test('department export map transforms data correctly', function () {
    $department = Department::factory()->create([
        'name' => 'Engineering',
        'created_at' => '2023-01-01 10:00:00',
        'updated_at' => '2023-01-02 11:00:00',
    ]);

    $export = new DepartmentExport([]);
    $mapped = $export->map($department);

    expect($mapped)->toBe([
        $department->id,
        'Engineering',
        '2023-01-01 10:00:00',
        '2023-01-02 11:00:00',
    ]);
});

test('department export handles null timestamps', function () {
    $department = Department::factory()->create([
        'name' => 'Test Department',
        'created_at' => null,
        'updated_at' => null,
    ]);

    $export = new DepartmentExport([]);
    $mapped = $export->map($department);

    expect($mapped)->toBe([
        $department->id,
        'Test Department',
        null,
        null,
    ]);
});
