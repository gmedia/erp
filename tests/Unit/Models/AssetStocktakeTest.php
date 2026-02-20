<?php

use App\Models\AssetStocktake;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('asset-stocktakes');

test('factory creates a valid asset stocktake', function () {
    $stocktake = AssetStocktake::factory()->create();

    assertDatabaseHas('asset_stocktakes', ['id' => $stocktake->id]);

    expect($stocktake->reference)->not->toBeNull()
        ->and($stocktake->branch_id)->not->toBeNull()
        ->and($stocktake->planned_at)->not->toBeNull()
        ->and($stocktake->status)->not->toBeNull();
});

test('casts are applied correctly', function () {
    $stocktake = AssetStocktake::factory()->create([
        'planned_at' => '2024-01-01',
        'performed_at' => '2024-01-02',
    ]);

    expect($stocktake->planned_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($stocktake->performed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new AssetStocktake)->getFillable();

    expect($fillable)->toContain(
        'branch_id',
        'reference',
        'planned_at',
        'performed_at',
        'status',
        'created_by',
    );
});

test('it belongs to a branch', function () {
    $branch = Branch::factory()->create();
    $stocktake = AssetStocktake::factory()->create(['branch_id' => $branch->id]);
    expect($stocktake->branch)->toBeInstanceOf(Branch::class)
        ->and($stocktake->branch->id)->toBe($branch->id);
});

test('it belongs to a creator', function () {
    $user = User::factory()->create();
    $stocktake = AssetStocktake::factory()->create(['created_by' => $user->id]);
    expect($stocktake->createdBy)->toBeInstanceOf(User::class)
        ->and($stocktake->createdBy->id)->toBe($user->id);
});
