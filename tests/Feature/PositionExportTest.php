<?php

use App\Exports\PositionExport;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('PositionExport', function () {

    test('query applies search filter case-insensitively', function () {
        Position::factory()->create(['name' => 'Software Engineer']);
        Position::factory()->create(['name' => 'Product Manager']);
        Position::factory()->create(['name' => 'Marketing Specialist']);

        $export = new PositionExport(['search' => 'ENG']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Software Engineer');
    });

    test('query applies exact name filter', function () {
        Position::factory()->create(['name' => 'Senior Developer']);
        Position::factory()->create(['name' => 'Product Manager']);
        Position::factory()->create(['name' => 'Designer']);

        $export = new PositionExport(['name' => 'Senior Developer']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Senior Developer');
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
        $oldPos = Position::factory()->create(['name' => 'Old Position']);
        $oldPos->created_at = now()->subDays(2);
        $oldPos->save();

        $newPos = Position::factory()->create(['name' => 'New Position']);
        $newPos->created_at = now();
        $newPos->save();

        $export = new PositionExport([]);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('New Position')
            ->and($results[1]->name)->toBe('Old Position');
    });

    test('query does not allow invalid sort columns', function () {
        Position::factory()->create(['name' => 'Test Position']);

        $export = new PositionExport(['sort_by' => 'invalid_column']);

        $query = $export->query();

        // Should not throw error, just ignore invalid sort
        expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
        expect($query->get())->toHaveCount(1);
    });

    test('query combines search and sorting', function () {
        Position::factory()->create(['name' => 'Zeta Manager']);
        Position::factory()->create(['name' => 'Alpha Manager']);
        Position::factory()->create(['name' => 'Developer']);

        $export = new PositionExport([
            'search' => 'manager',
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2)
            ->and($results[0]->name)->toBe('Alpha Manager')
            ->and($results[1]->name)->toBe('Zeta Manager');
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

    test('map transforms position data correctly with timestamps', function () {
        $position = Position::factory()->create([
            'name' => 'Senior Software Engineer',
            'created_at' => '2023-02-15 16:45:00',
            'updated_at' => '2023-02-20 11:30:00',
        ]);

        $export = new PositionExport([]);
        $mapped = $export->map($position);

        expect($mapped)->toBe([
            $position->id,
            'Senior Software Engineer',
            '2023-02-15 16:45:00',
            '2023-02-20 11:30:00',
        ]);
    });

    test('map handles null timestamps gracefully', function () {
        $position = Position::factory()->create([
            'name' => 'Test Position',
            'created_at' => null,
            'updated_at' => null,
        ]);

        $export = new PositionExport([]);
        $mapped = $export->map($position);

        expect($mapped)->toBe([
            $position->id,
            'Test Position',
            null,
            null,
        ]);
    });

    test('map handles carbon timestamp objects', function () {
        $position = Position::factory()->create([
            'name' => 'Carbon Test Position',
        ]);

        // Ensure timestamps are Carbon instances
        expect($position->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);

        $export = new PositionExport([]);
        $mapped = $export->map($position);

        expect($mapped[0])->toBe($position->id)
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
