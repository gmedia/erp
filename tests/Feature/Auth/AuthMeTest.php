<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('auth');

test('me endpoint falls back for menu bootstrap failures in testing', function () {
    $user = createTestUserWithPermissions(['warehouse']);

    Schema::dropIfExists('menu_permission');
    Schema::drop('menus');

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/me');

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('menus', [])
        ->assertJsonStructure([
            'user',
            'employee',
            'companyName',
            'companyLogoUrl',
            'regionalSettings',
            'menus',
            'pendingApprovalsCount',
            'translations',
            'locale',
        ]);
});
