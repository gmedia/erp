<?php

use App\Models\Asset;
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

it('cannot view asset register report without permission', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, ['*']);
    $response = $this->getJson('/api/reports/assets/register');

    $response->assertStatus(403);
})->group('asset-reports');
