<?php

use App\Models\AssetLocation;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('asset-locations');

test('factory creates a valid asset location', function () {
    $assetLocation = AssetLocation::factory()->create();

    assertDatabaseHas('asset_locations', ['id' => $assetLocation->id]);

    expect($assetLocation->getAttributes())->toMatchArray([
        'code' => $assetLocation->code,
        'name' => $assetLocation->name,
        'branch_id' => $assetLocation->branch_id,
    ]);
});

test('asset location belongs to a branch', function () {
    $branch = Branch::factory()->create();
    $assetLocation = AssetLocation::factory()->create(['branch_id' => $branch->id]);

    expect($assetLocation->branch)->toBeInstanceOf(Branch::class)
        ->and($assetLocation->branch->id)->toBe($branch->id);
});

test('asset location can have a parent', function () {
    $branch = Branch::factory()->create();
    $parent = AssetLocation::factory()->create(['branch_id' => $branch->id]);
    $child = AssetLocation::factory()->create([
        'branch_id' => $branch->id,
        'parent_id' => $parent->id,
    ]);

    expect($child->parent)->toBeInstanceOf(AssetLocation::class)
        ->and($child->parent->id)->toBe($parent->id);
});

test('asset location can have children', function () {
    $branch = Branch::factory()->create();
    $parent = AssetLocation::factory()->create(['branch_id' => $branch->id]);
    AssetLocation::factory()->count(2)->create([
        'branch_id' => $branch->id,
        'parent_id' => $parent->id,
    ]);

    expect($parent->children)->toHaveCount(2);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new AssetLocation)->getFillable();

    expect($fillable)->toBe([
        'branch_id',
        'parent_id',
        'code',
        'name',
    ]);
});
