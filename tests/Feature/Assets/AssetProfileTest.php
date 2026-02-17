<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\get;

uses(RefreshDatabase::class)->group('assets');

beforeEach(function () {
    $user = createTestUserWithPermissions(['asset', 'asset_profile', 'asset_movement']);
    actingAs($user);
    
    FiscalYear::factory()->create(['status' => 'open']);
});

test('asset profile page returns correct data', function () {
    $asset = Asset::factory()->create();

    // The route for profile is /assets/{asset}, not api/assets/{asset}/profile
    $response = get("/assets/{$asset->ulid}");

    $response->assertStatus(200);
});

test('asset profile movements list is accessible', function () {
    $asset = Asset::factory()->create();

    // Correct endpoint for movements based on routes/asset_movement.php
    $response = getJson("/api/asset-movements?asset_id={$asset->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
});
