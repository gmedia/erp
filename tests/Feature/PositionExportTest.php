<?php

use App\Exports\PositionExport;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('positions');

describe('PositionExport', function () {

    test('query applies search filter', function () {
        Position::factory()->create(['name' => 'Manager']);
        Position::factory()->create(['name' => 'Staff']);

        $export = new PositionExport(['search' => 'Man']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Manager');
    });

    test('map function returns correct data', function () {
        $position = Position::factory()->make([
            'id' => 1,
            'name' => 'Test Pos',
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new PositionExport([]);
        $mapped = $export->map($position);

        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1);
        expect($mapped[1])->toBe('Test Pos');
    });

    test('headings returns correct columns', function () {
        $export = new PositionExport([]);
        
        expect($export->headings())->toContain('ID', 'Name', 'Created At');
    });
});
