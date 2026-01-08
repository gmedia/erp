<?php

use App\Exports\DepartmentExport;
use App\Exports\PositionExport;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CRUD Export Classes', function () {

    dataset('crud_exports', [
        [DepartmentExport::class, Department::class, 'Department'],
        [PositionExport::class, Position::class, 'Position'],
    ]);

    test('{2}Export query applies search filter case-insensitively', function ($exportClass, $modelClass, $name) {
        $modelClass::factory()->create(['name' => 'Engineering ' . $name]);
        $modelClass::factory()->create(['name' => 'Marketing ' . $name]);
        $modelClass::factory()->create(['name' => 'Sales ' . $name]);

        $export = new $exportClass(['search' => 'ENG']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Engineering ' . $name);
    })->with('crud_exports');

    test('{2}Export query applies exact name filter', function ($exportClass, $modelClass, $name) {
        $modelClass::factory()->create(['name' => 'Engineering']);
        $modelClass::factory()->create(['name' => 'Marketing']);
        $modelClass::factory()->create(['name' => 'Sales']);

        $export = new $exportClass(['name' => 'Engineering']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Engineering');
    })->with('crud_exports');

    test('{2}Export query applies ascending sort by name', function ($exportClass, $modelClass, $name) {
        $modelClass::factory()->create(['name' => 'Zeta ' . $name]);
        $modelClass::factory()->create(['name' => 'Alpha ' . $name]);
        $modelClass::factory()->create(['name' => 'Beta ' . $name]);

        $export = new $exportClass(['sort_by' => 'name', 'sort_direction' => 'asc']);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('Alpha ' . $name)
            ->and($results[1]->name)->toBe('Beta ' . $name)
            ->and($results[2]->name)->toBe('Zeta ' . $name);
    })->with('crud_exports');

    test('{2}Export query applies descending sort by created_at when no sort specified', function ($exportClass, $modelClass, $name) {
        $oldItem = $modelClass::factory()->create(['name' => 'Old ' . $name]);
        $oldItem->created_at = now()->subDays(2);
        $oldItem->save();

        $newItem = $modelClass::factory()->create(['name' => 'New ' . $name]);
        $newItem->created_at = now();
        $newItem->save();

        $export = new $exportClass([]);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('New ' . $name)
            ->and($results[1]->name)->toBe('Old ' . $name);
    })->with('crud_exports');

    test('{2}Export query does not allow invalid sort columns', function ($exportClass, $modelClass, $name) {
        $modelClass::factory()->create(['name' => 'Test ' . $name]);

        $export = new $exportClass(['sort_by' => 'invalid_column']);

        // Should not throw error, just ignore invalid sort
        $results = $export->query()->get();

        expect($results)->toHaveCount(1);
    })->with('crud_exports');

    test('{2}Export query combines search and sorting', function ($exportClass, $modelClass, $name) {
        $modelClass::factory()->create(['name' => 'Zeta Engineering']);
        $modelClass::factory()->create(['name' => 'Alpha Engineering']);
        $modelClass::factory()->create(['name' => 'Marketing']);

        $export = new $exportClass([
            'search' => 'engineering',
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2)
            ->and($results[0]->name)->toBe('Alpha Engineering')
            ->and($results[1]->name)->toBe('Zeta Engineering');
    })->with('crud_exports');

    test('{2}Export headings returns correct column headers', function ($exportClass, $modelClass, $name) {
        $export = new $exportClass([]);

        $headings = $export->headings();

        expect($headings)->toBe([
            'ID',
            'Name',
            'Created At',
            'Updated At',
        ]);
    })->with('crud_exports');

    test('{2}Export map transforms data correctly with timestamps', function ($exportClass, $modelClass, $name) {
        $item = $modelClass::factory()->create([
            'name' => 'Engineering ' . $name,
            'created_at' => '2023-01-15 14:30:00',
            'updated_at' => '2023-01-20 09:15:00',
        ]);

        $export = new $exportClass([]);
        $mapped = $export->map($item);

        expect($mapped)->toBe([
            $item->id,
            'Engineering ' . $name,
            '2023-01-15 14:30:00',
            '2023-01-20 09:15:00',
        ]);
    })->with('crud_exports');

    test('{2}Export map handles null timestamps gracefully', function ($exportClass, $modelClass, $name) {
        $item = $modelClass::factory()->create([
            'name' => 'Test ' . $name,
            'created_at' => null,
            'updated_at' => null,
        ]);

        $export = new $exportClass([]);
        $mapped = $export->map($item);

        expect($mapped)->toBe([
            $item->id,
            'Test ' . $name,
            null,
            null,
        ]);
    })->with('crud_exports');

    test('{2}Export map handles carbon timestamp objects', function ($exportClass, $modelClass, $name) {
        $item = $modelClass::factory()->create([
            'name' => 'Carbon Test ' . $name,
        ]);

        // Ensure timestamps are Carbon instances
        expect($item->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);

        $export = new $exportClass([]);
        $mapped = $export->map($item);

        expect($mapped[0])->toBe($item->id)
            ->and($mapped[1])->toBe('Carbon Test ' . $name)
            ->and($mapped[2])->toBeString()
            ->and($mapped[3])->toBeString();
    })->with('crud_exports');

    test('{2}Export handles empty filters gracefully', function ($exportClass, $modelClass, $name) {
        $modelClass::factory()->count(3)->create();

        $export = new $exportClass([]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(3);
    })->with('crud_exports');

});
