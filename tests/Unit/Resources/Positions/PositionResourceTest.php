<?php

use App\Http\Resources\Positions\PositionResource;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('positions');

test('toArray transforms position correctly', function () {
    $position = Position::factory()->create([
        'name' => 'Software Engineer',
        'created_at' => '2023-01-01 10:00:00',
        'updated_at' => '2023-01-02 11:00:00',
    ]);

    $resource = new PositionResource($position);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKey('id', $position->id)
        ->and($result)->toHaveKey('name', 'Software Engineer')
        ->and($result['created_at'])->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($result['updated_at'])->toBeInstanceOf(\Carbon\Carbon::class);
});

test('toArray includes all required fields', function () {
    $position = Position::factory()->create();

    $resource = new PositionResource($position);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKeys(['id', 'name', 'created_at', 'updated_at'])
        ->and($result['id'])->toBe($position->id)
        ->and($result['name'])->toBe($position->name);
});

test('toArray handles null timestamps', function () {
    $position = Position::factory()->create();
    $position->created_at = null;
    $position->updated_at = null;

    $resource = new PositionResource($position);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result['created_at'])->toBeNull()
        ->and($result['updated_at'])->toBeNull();
});
