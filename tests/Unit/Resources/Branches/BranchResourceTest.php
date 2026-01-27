<?php

use App\Http\Resources\Branches\BranchResource;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('branches');

test('toArray transforms branch correctly', function () {
    $branch = Branch::factory()->create([
        'name' => 'Jakarta Branch',
        'created_at' => '2023-01-01 10:00:00',
        'updated_at' => '2023-01-02 11:00:00',
    ]);

    $resource = new BranchResource($branch);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKey('id', $branch->id)
        ->and($result)->toHaveKey('name', 'Jakarta Branch')
        ->and($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});

test('toArray includes all required fields', function () {
    $branch = Branch::factory()->create();

    $resource = new BranchResource($branch);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKeys(['id', 'name', 'created_at', 'updated_at'])
        ->and($result['id'])->toBe($branch->id)
        ->and($result['name'])->toBe($branch->name);
});

test('toArray handles null timestamps', function () {
    $branch = Branch::factory()->create();
    $branch->created_at = null;
    $branch->updated_at = null;

    $resource = new BranchResource($branch);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result['created_at'])->toBeNull()
        ->and($result['updated_at'])->toBeNull();
});
