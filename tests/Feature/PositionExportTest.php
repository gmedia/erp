<?php

use App\Exports\PositionExport;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('positions');

describe('PositionExport', function () {
    beforeEach(function () {
        Position::query()->delete();
    });

    test('query applies search filter case-insensitively', function () {
        Position::factory()->create(['name' => 'Engineering Position']);
        Position::factory()->create(['name' => 'Marketing Position']);
        Position::factory()->create(['name' => 'Sales Position']);

        $export = new PositionExport(['search' => 'ENG']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Engineering Position');
    });

    test('query applies exact name filter', function () {
        Position::factory()->create(['name' => 'Engineering']);
        Position::factory()->create(['name' => 'Marketing']);
        Position::factory()->create(['name' => 'Sales']);

        $export = new PositionExport(['name' => 'Engineering']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Engineering');
    });

    test('query applies ascending sort by name', function () {
        Position::factory()->create(['name' => 'Zeta Position']);
        Position::factory()->create(['name' => 'Alpha Position']);
        Position::factory()->create(['name' => 'Beta Position']);

        $export = new PositionExport(['sort_by' => 'name', 'sort_direction' => 'asc']);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('Alpha Position')
            ->and($results[1]->name)->toBe('Beta Position')
            ->and($results[2]->name)->toBe('Zeta Position');
    });

    test('query applies descending sort by created_at when no sort specified', function () {
        $oldItem = Position::factory()->create(['name' => 'Old Position']);
        $oldItem->created_at = now()->subDays(2);
        $oldItem->save();

        $newItem = Position::factory()->create(['name' => 'New Position']);
        $newItem->created_at = now();
        $newItem->save();

        $export = new PositionExport([]);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('New Position')
            ->and($results[1]->name)->toBe('Old Position');
    });

    test('query does not allow invalid sort columns', function () {
        Position::factory()->create(['name' => 'Test Position']);

        $export = new PositionExport(['sort_by' => 'invalid_column']);

        // Should not throw error, just ignore invalid sort
        $results = $export->query()->get();

        expect($results)->toHaveCount(1);
    });

    test('query combines search and sorting', function () {
        Position::factory()->create(['name' => 'Zeta Engineering']);
        Position::factory()->create(['name' => 'Alpha Engineering']);
        Position::factory()->create(['name' => 'Marketing']);

        $export = new PositionExport([
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
        $export = new PositionExport([]);

        $headings = $export->headings();

        expect($headings)->toBe([
            'ID',
            'Name',
            'Created At',
            'Updated At',
        ]);
    });

    test('map transforms data correctly with timestamps', function () {
        $item = Position::factory()->create([
            'name' => 'Engineering Position',
            'created_at' => '2023-01-15 14:30:00',
            'updated_at' => '2023-01-20 09:15:00',
        ]);

        $export = new PositionExport([]);
        $mapped = $export->map($item);

        expect($mapped)->toBe([
            $item->id,
            'Engineering Position',
            '2023-01-15T14:30:00+00:00',
            '2023-01-20T09:15:00+00:00',
        ]);
    });

    test('map handles null timestamps gracefully', function () {
        $item = Position::factory()->create([
            'name' => 'Test Position',
            'created_at' => null,
            'updated_at' => null,
        ]);

        $export = new PositionExport([]);
        $mapped = $export->map($item);

        expect($mapped)->toBe([
            $item->id,
            'Test Position',
            null,
            null,
        ]);
    });

    test('map handles carbon timestamp objects', function () {
        $item = Position::factory()->create([
            'name' => 'Carbon Test Position',
        ]);

        // Ensure timestamps are Carbon instances
        expect($item->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);

        $export = new PositionExport([]);
        $mapped = $export->map($item);

        expect($mapped[0])->toBe($item->id)
            ->and($mapped[1])->toBe('Carbon Test Position')
            ->and($mapped[2])->toBeString()
            ->and($mapped[3])->toBeString();
    });

    test('handles empty filters gracefully', function () {
        Position::factory()->count(3)->create();

        $export = new PositionExport([]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(3);
    });
});
