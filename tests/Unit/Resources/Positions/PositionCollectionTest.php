<?php

use App\Http\Resources\Positions\PositionCollection;
use App\Http\Resources\Positions\PositionResource;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('positions');

test('collects property is set correctly', function () {
    $collection = new PositionCollection([]);

    expect($collection->collects)->toBe(PositionResource::class);
});

test('collection transforms multiple positions correctly', function () {
    $positions = Position::factory()->count(3)->create();

    $collection = new PositionCollection($positions);
    $request = new Request;

    $result = $collection->toArray($request);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(3);

    foreach ($result as $index => $item) {
        expect($item)->toHaveKeys(['id', 'name', 'created_at', 'updated_at'])
            ->and($item['id'])->toBe($positions[$index]->id)
            ->and($item['name'])->toBe($positions[$index]->name);
    }
});

test('collection returns empty array when no positions', function () {
    $collection = new PositionCollection(collect());
    $request = new Request;

    $result = $collection->toArray($request);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(0);
});
