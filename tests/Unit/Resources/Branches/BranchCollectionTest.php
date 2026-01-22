<?php

use App\Http\Resources\Branches\BranchCollection;
use App\Http\Resources\Branches\BranchResource;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('collects property is set correctly', function () {
    $collection = new BranchCollection([]);

    expect($collection->collects)->toBe(BranchResource::class);
});

test('collection transforms multiple branches correctly', function () {
    $branches = Branch::factory()->count(3)->create();

    $collection = new BranchCollection($branches);
    $request = new Request;

    $result = $collection->toArray($request);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(3);

    foreach ($result as $index => $item) {
        expect($item)->toHaveKeys(['id', 'name', 'created_at', 'updated_at'])
            ->and($item['id'])->toBe($branches[$index]->id)
            ->and($item['name'])->toBe($branches[$index]->name);
    }
});

test('collection returns empty array when no branches', function () {
    $collection = new BranchCollection(collect());
    $request = new Request;

    $result = $collection->toArray($request);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(0);
});
