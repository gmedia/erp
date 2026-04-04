<?php

namespace Tests\Feature\AssetStocktakes;

use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\AssetStocktake;
use App\Models\AssetStocktakeItem;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('asset-stocktakes');

beforeEach(function () {
    $this->user = createTestUserWithPermissions([
        'asset_stocktake',
    ]);
    Sanctum::actingAs($this->user, ['*']);
});

test('it can list variance items', function () {
    $branch = Branch::factory()->create();
    $location = AssetLocation::factory()->create(['branch_id' => $branch->id]);

    $stocktake = AssetStocktake::factory()->create([
        'branch_id' => $branch->id,
        'status' => 'completed',
    ]);

    $assetDamaged = Asset::factory()->create(['branch_id' => $branch->id]);
    $assetMissing = Asset::factory()->create(['branch_id' => $branch->id]);
    $assetFound = Asset::factory()->create(['branch_id' => $branch->id]);

    AssetStocktakeItem::factory()->create([
        'asset_stocktake_id' => $stocktake->id,
        'asset_id' => $assetDamaged->id,
        'result' => 'damaged',
    ]);

    AssetStocktakeItem::factory()->create([
        'asset_stocktake_id' => $stocktake->id,
        'asset_id' => $assetMissing->id,
        'result' => 'missing',
    ]);

    // This should NOT be in the variance report
    AssetStocktakeItem::factory()->create([
        'asset_stocktake_id' => $stocktake->id,
        'asset_id' => $assetFound->id,
        'result' => 'found',
    ]);

    $response = getJson('/api/asset-stocktake-variances');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('it can filter variance by stocktake and result', function () {
    $branch = Branch::factory()->create();
    $stocktake = AssetStocktake::factory()->create(['branch_id' => $branch->id]);

    $asset = Asset::factory()->create(['branch_id' => $branch->id]);

    AssetStocktakeItem::factory()->create([
        'asset_stocktake_id' => $stocktake->id,
        'asset_id' => $asset->id,
        'result' => 'damaged',
    ]);

    $response = getJson('/api/asset-stocktake-variances?result=missing');
    $response->assertOk()->assertJsonCount(0, 'data');

    $response = getJson('/api/asset-stocktake-variances?result=damaged');
    $response->assertOk()->assertJsonCount(1, 'data');

    // Filter by stocktake
    $response = getJson('/api/asset-stocktake-variances?asset_stocktake_id=' . $stocktake->id);
    $response->assertOk()->assertJsonCount(1, 'data');

    // Invalid stocktake
    $response = getJson('/api/asset-stocktake-variances?asset_stocktake_id=99999');
    $response->assertStatus(422);
});

test('it can export variance to excel', function () {
    Carbon::setTestNow('2026-04-04 15:30:45');
    Excel::fake();
    Storage::fake('public');

    $branch = Branch::factory()->create();
    $stocktake = AssetStocktake::factory()->create(['branch_id' => $branch->id]);

    $asset = Asset::factory()->create(['branch_id' => $branch->id]);

    AssetStocktakeItem::factory()->create([
        'asset_stocktake_id' => $stocktake->id,
        'asset_id' => $asset->id,
        'result' => 'damaged',
    ]);

    $response = postJson('/api/asset-stocktake-variances/export');

    $response->assertOk()
        ->assertJsonStructure([
            'url',
            'filename',
        ]);

    $filename = $response->json('filename');

    expect($filename)->toBe('asset_stocktake_variances_20260404_153045.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
