<?php

use App\Models\Asset;
use App\Models\User;

it('can view asset register report', function () {
    $user = createTestUserWithPermissions(['asset']);

    $asset = Asset::factory()->create([
        'name' => 'Test Asset XYZ',
    ]);

    $response = $this->actingAs($user)->getJson(route('reports.assets.register'));

    $response->assertStatus(200)
        ->assertJsonFragment(['name' => 'Test Asset XYZ']);
})->group('asset-reports');

it('cannot view asset register report without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('reports.assets.register'));

    $response->assertStatus(403);
})->group('asset-reports');
