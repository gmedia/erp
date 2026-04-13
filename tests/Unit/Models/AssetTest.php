<?php

namespace Tests\Unit\Models;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('assets');

test('asset belongs to a branch', function () {
    $branch = Branch::factory()->create();
    $asset = Asset::factory()->create(['branch_id' => $branch->id]);

    expect($asset->branch->id)->toBe($branch->id);
});

test('asset belongs to a category', function () {
    $category = AssetCategory::factory()->create();
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);

    expect($asset->category->id)->toBe($category->id);
});

test('asset belongs to a supplier', function () {
    $supplier = Supplier::factory()->create();
    $asset = Asset::factory()->create(['supplier_id' => $supplier->id]);

    expect($asset->supplier->id)->toBe($supplier->id);
});

test('asset has ulid', function () {
    $asset = Asset::factory()->create();

    expect($asset->ulid)->not->toBeNull();
});

test('asset can be soft deleted', function () {
    $asset = Asset::factory()->create();
    $asset->delete();

    expect($asset->deleted_at)->not->toBeNull();
});
