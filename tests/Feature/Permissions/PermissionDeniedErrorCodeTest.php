<?php

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('permissions');

test('permission middleware 403 includes stable error_code with unchanged english message', function () {
    $user = createTestUserWithPermissions([]);
    Sanctum::actingAs($user, ['*']);

    getJson('/api/pipelines')
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'permission.denied')
        ->assertJsonPath('message', 'You do not have permission to perform this action.');
});

test('entity-state-permission 403 includes stable error_code with unchanged english message', function () {
    $user = createTestUserWithPermissions(['purchase_order']);
    Sanctum::actingAs($user, ['*']);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    getJson("/api/entity-states/asset/{$purchaseOrder->id}")
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'permission.denied')
        ->assertJsonPath('message', 'You do not have permission to perform this action.');
});
