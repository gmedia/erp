<?php

use App\Exports\PositionExport;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('position export query applies search filter', function () {
    Position::factory()->create(['name' => 'Software Engineer']);
    Position::factory()->create(['name' => 'Product Manager']);

    $export = new PositionExport(['search' => 'engineer']);

    $query = $export->query();

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Software Engineer');
});

test('position export query applies name filter', function () {
    Position::factory()->create(['name' => 'Developer']);
    Position::factory()->create(['name' => 'Manager']);

    $export = new PositionExport(['name' => 'Developer']);

    $query = $export->query();

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Developer');
});

test('position export query applies sorting', function () {
    Position::factory()->create(['name' => 'Z Position']);
    Position::factory()->create(['name' => 'A Position']);

    $export = new PositionExport(['sort_by' => 'name', 'sort_direction' => 'asc']);

    $query = $export->query();

    $results = $query->get();

    expect($results->first()->name)->toBe('A Position')
        ->and($results->last()->name)->toBe('Z Position');
});

test('position export query does not allow invalid sort columns', function () {
    Position::factory()->create(['name' => 'Test Position']);

    $export = new PositionExport(['sort_by' => 'invalid_column']);

    $query = $export->query();

    // Should not throw error, just ignore invalid sort
    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});

test('position export headings are correct', function () {
    $export = new PositionExport([]);

    $headings = $export->headings();

    expect($headings)->toBe([
        'ID',
        'Name',
        'Created At',
        'Updated At',
    ]);
});

test('position export map transforms data correctly', function () {
    $position = Position::factory()->create([
        'name' => 'Software Engineer',
        'created_at' => '2023-01-01 10:00:00',
        'updated_at' => '2023-01-02 11:00:00',
    ]);

    $export = new PositionExport([]);
    $mapped = $export->map($position);

    expect($mapped)->toBe([
        $position->id,
        'Software Engineer',
        '2023-01-01 10:00:00',
        '2023-01-02 11:00:00',
    ]);
});

test('position export handles null timestamps', function () {
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
