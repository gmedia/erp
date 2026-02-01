<?php

use App\Exports\DepartmentExport;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('departments');

describe('DepartmentExport', function () {

    test('query applies search filter', function () {
        Department::factory()->create(['name' => 'Engineering']);
        Department::factory()->create(['name' => 'Sales']);

        $export = new DepartmentExport(['search' => 'Engine']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Engineering');
    });

    test('map function returns correct data', function () {
        $department = Department::factory()->make([
            'id' => 1,
            'name' => 'Test Dept',
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new DepartmentExport([]);
        $mapped = $export->map($department);

        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1);
        expect($mapped[1])->toBe('Test Dept');
    });

    test('headings returns correct columns', function () {
        $export = new DepartmentExport([]);
        
        expect($export->headings())->toContain('ID', 'Name', 'Created At');
    });
});
