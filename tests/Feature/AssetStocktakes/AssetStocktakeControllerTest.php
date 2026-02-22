<?php

use App\Models\AssetStocktake;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

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
});

test('it can list asset stocktakes', function () {
    AssetStocktake::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
    ]);

    $response = getJson('/api/asset-stocktakes');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('it can create asset stocktake', function () {
    $data = [
        'branch_id' => $this->branch->id,
        'reference' => 'ST-2024-001',
        'planned_at' => '2024-01-01',
        'status' => 'draft',
    ];

    $response = postJson('/api/asset-stocktakes', $data);

    $response->assertCreated();
    assertDatabaseHas('asset_stocktakes', ['reference' => 'ST-2024-001']);
});

test('it can update asset stocktake', function () {
    $stocktake = AssetStocktake::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $response = putJson("/api/asset-stocktakes/{$stocktake->ulid}", [
        'reference' => 'ST-UPDATED',
        // branch_id not sent, validation rule might require it if not careful, 
        // but my UpdateRequest had 'sometimes' and exists check.
        // Wait, 'branch_id' => ['sometimes', 'required', ...]
        // If I don't send it, it's fine.
    ]);

    $response->assertOk();
    assertDatabaseHas('asset_stocktakes', ['reference' => 'ST-UPDATED']);
});

test('it can delete asset stocktake', function () {
    $stocktake = AssetStocktake::factory()->create();

    $response = deleteJson("/api/asset-stocktakes/{$stocktake->ulid}");

    $response->assertNoContent();
    assertDatabaseMissing('asset_stocktakes', ['id' => $stocktake->id]);
});
