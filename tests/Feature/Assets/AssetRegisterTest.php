<?php

use App\Models\Asset;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('assets');

it('can view asset register report', function () {
    $user = createTestUserWithPermissions(['asset']);

    $asset = Asset::factory()->create([
        'name' => 'Test Asset XYZ',
    ]);

    Sanctum::actingAs($user, ['*']);
    $response = $this->getJson('/api/reports/assets/register');

    $response->assertStatus(200)
        ->assertJsonFragment(['name' => 'Test Asset XYZ']);
})->group('asset-reports');

it('returns total purchase cost across all filtered assets', function () {
    $user = createTestUserWithPermissions(['asset']);
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    Asset::factory()->create(['branch_id' => $branchA->id, 'purchase_cost' => 1000000]);
    Asset::factory()->create(['branch_id' => $branchA->id, 'purchase_cost' => 2500000]);
    Asset::factory()->create(['branch_id' => $branchB->id, 'purchase_cost' => 4000000]);

    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/reports/assets/register')
        ->assertOk()
        ->assertJsonPath('summary.totals.purchase_cost', '7500000.00')
        ->assertJsonCount(3, 'data');

    $this->getJson('/api/reports/assets/register?branch_id=' . $branchA->id)
        ->assertOk()
        ->assertJsonPath('summary.totals.purchase_cost', '3500000.00')
        ->assertJsonCount(2, 'data');
})->group('asset-reports');

it('cannot view asset register report without permission', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, ['*']);
    $response = $this->getJson('/api/reports/assets/register');

    $response->assertStatus(403);
})->group('asset-reports');
