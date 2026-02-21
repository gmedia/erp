<?php

use App\Models\Asset;
use App\Models\AssetStocktake;
use App\Models\AssetStocktakeItem;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('asset-stocktakes');

beforeEach(function () {
    $this->user = createTestUserWithPermissions([
        'asset_stocktake',
        'asset_stocktake.create',
        'asset_stocktake.edit',
        'asset_stocktake.delete'
    ]);
    actingAs($this->user);
    $this->branch = Branch::factory()->create();
    
    $this->stocktake = AssetStocktake::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => 'draft',
    ]);
});

test('it generates expected items when none exist', function () {
    // Create some active assets in this branch
    $asset1 = Asset::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => 'active',
    ]);
    $asset2 = Asset::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => 'active',
    ]);
    
    // Create an asset in different branch
    Asset::factory()->create(['status' => 'active']);

    $response = getJson("/api/asset-stocktakes/{$this->stocktake->id}/items");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
        
    $data = $response->json('data');
    expect($data[0]['asset_id'])->toBe($asset1->id);
    expect($data[1]['asset_id'])->toBe($asset2->id);
});

test('it returns saved items if exist', function () {
    $asset = Asset::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => 'active',
    ]);
    
    AssetStocktakeItem::factory()->create([
        'asset_stocktake_id' => $this->stocktake->id,
        'asset_id' => $asset->id,
        'result' => 'found',
    ]);

    $response = getJson("/api/asset-stocktakes/{$this->stocktake->id}/items");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
        
    $data = $response->json('data');
    expect($data[0]['result'])->toBe('found');
});

test('it can sync items and update status', function () {
    $asset = Asset::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    $payload = [
        'items' => [
            [
                'asset_id' => $asset->id,
                'expected_branch_id' => $this->branch->id,
                'result' => 'found',
                'notes' => 'All good',
            ]
        ]
    ];

    $response = postJson("/api/asset-stocktakes/{$this->stocktake->id}/items", $payload);

    $response->assertStatus(200);

    assertDatabaseHas('asset_stocktake_items', [
        'asset_stocktake_id' => $this->stocktake->id,
        'asset_id' => $asset->id,
        'result' => 'found',
        'notes' => 'All good',
    ]);

    // Stocktake status should auto update to in_progress
    assertDatabaseHas('asset_stocktakes', [
        'id' => $this->stocktake->id,
        'status' => 'in_progress',
    ]);
});
