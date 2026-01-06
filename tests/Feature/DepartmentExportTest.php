<?php

use App\Exports\DepartmentExport;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DepartmentExport', function () {

    test('query applies search filter case-insensitively', function () {
        Department::factory()->create(['name' => 'Engineering Department']);
        Department::factory()->create(['name' => 'Marketing Team']);
        Department::factory()->create(['name' => 'Sales Division']);

        $export = new DepartmentExport(['search' => 'ENG']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Engineering Department');
    });

    test('query applies exact name filter', function () {
        Department::factory()->create(['name' => 'Engineering']);
        Department::factory()->create(['name' => 'Marketing']);
        Department::factory()->create(['name' => 'Sales']);

        $export = new DepartmentExport(['name' => 'Engineering']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Engineering');
    });

    test('query applies ascending sort by name', function () {
        Department::factory()->create(['name' => 'Zeta Department']);
        Department::factory()->create(['name' => 'Alpha Department']);
        Department::factory()->create(['name' => 'Beta Department']);

        $export = new DepartmentExport(['sort_by' => 'name', 'sort_direction' => 'asc']);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('Alpha Department')
            ->and($results[1]->name)->toBe('Beta Department')
            ->and($results[2]->name)->toBe('Zeta Department');
    });

    test('query applies descending sort by created_at when no sort specified', function () {
        $oldDept = Department::factory()->create(['name' => 'Old Department']);
        $oldDept->created_at = now()->subDays(2);
        $oldDept->save();

        $newDept = Department::factory()->create(['name' => 'New Department']);
        $newDept->created_at = now();
        $newDept->save();

        $export = new DepartmentExport([]);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('New Department')
            ->and($results[1]->name)->toBe('Old Department');
    });

    test('query does not allow invalid sort columns', function () {
        Department::factory()->create(['name' => 'Test Department']);

        $export = new DepartmentExport(['sort_by' => 'invalid_column']);

        // Should not throw error, just ignore invalid sort
        $results = $export->query()->get();

        expect($results)->toHaveCount(1);
    });

    test('query combines search and sorting', function () {
        Department::factory()->create(['name' => 'Zeta Engineering']);
        Department::factory()->create(['name' => 'Alpha Engineering']);
        Department::factory()->create(['name' => 'Marketing']);

        $export = new DepartmentExport([
            'search' => 'engineering',
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2)
            ->and($results[0]->name)->toBe('Alpha Engineering')
            ->and($results[1]->name)->toBe('Zeta Engineering');
    });

    test('headings returns correct column headers', function () {
        $export = new DepartmentExport([]);

        $headings = $export->headings();

        expect($headings)->toBe([
            'ID',
            'Name',
            'Created At',
            'Updated At',
        ]);
    });

    test('map transforms department data correctly with timestamps', function () {
        $department = Department::factory()->create([
            'name' => 'Engineering Department',
            'created_at' => '2023-01-15 14:30:00',
            'updated_at' => '2023-01-20 09:15:00',
        ]);

        $export = new DepartmentExport([]);
        $mapped = $export->map($department);

        expect($mapped)->toBe([
            $department->id,
            'Engineering Department',
            '2023-01-15 14:30:00',
            '2023-01-20 09:15:00',
        ]);
    });

    test('map handles null timestamps gracefully', function () {
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

    test('map handles carbon timestamp objects', function () {
        $department = Department::factory()->create([
            'name' => 'Carbon Test Department',
        ]);

        // Ensure timestamps are Carbon instances
        expect($department->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);

        $export = new DepartmentExport([]);
        $mapped = $export->map($department);

        expect($mapped[0])->toBe($department->id)
            ->and($mapped[1])->toBe('Carbon Test Department')
            ->and($mapped[2])->toBeString()
            ->and($mapped[3])->toBeString();
    });

    test('handles empty filters gracefully', function () {
        Department::factory()->count(3)->create();

        $export = new DepartmentExport([]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(3);
    });

});
