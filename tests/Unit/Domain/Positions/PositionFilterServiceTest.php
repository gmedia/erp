<?php

use App\Domain\Positions\PositionFilterService;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('applySearch adds where clause for search term', function () {
    $service = new PositionFilterService;

    Position::factory()->create(['name' => 'Software Engineer']);
    Position::factory()->create(['name' => 'Product Manager']);
    Position::factory()->create(['name' => 'Designer']);

    $query = Position::query();
    $service->applySearch($query, 'engineer', ['name']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Software Engineer');
});

test('applySearch searches across multiple fields', function () {
    $service = new PositionFilterService;

    Position::factory()->create(['name' => 'Developer Position']);
    Position::factory()->create(['name' => 'Manager Position']);

    $query = Position::query();
    $service->applySearch($query, 'Position', ['name']);

    $results = $query->get();

    expect($results)->toHaveCount(2);
});

test('applySorting applies ascending sort when allowed', function () {
    $service = new PositionFilterService;

    Position::factory()->create(['name' => 'Z Position']);
    Position::factory()->create(['name' => 'A Position']);

    $query = Position::query();
    $service->applySorting($query, 'name', 'asc', ['id', 'name', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('A Position')
        ->and($results->last()->name)->toBe('Z Position');
});

test('applySorting applies descending sort when allowed', function () {
    $service = new PositionFilterService;

    Position::factory()->create(['name' => 'A Position']);
    Position::factory()->create(['name' => 'Z Position']);

    $query = Position::query();
    $service->applySorting($query, 'name', 'desc', ['id', 'name', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('Z Position')
        ->and($results->last()->name)->toBe('A Position');
});

test('applySorting does not apply sort when field not allowed', function () {
    $service = new PositionFilterService;

    Position::factory()->create(['name' => 'Test Position']);

    $query = Position::query();
    $originalSql = $query->toSql();

    $service->applySorting($query, 'invalid_field', 'asc', ['id', 'name', 'created_at', 'updated_at']);

    // SQL should remain unchanged since invalid field
    expect($query->toSql())->toBe($originalSql);
});
