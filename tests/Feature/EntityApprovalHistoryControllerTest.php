<?php

use App\Models\ApprovalRequest;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('approval-history');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['pipeline']);
});

test('can get approval history for supported entity', function () {
    $asset = Asset::factory()->create();
    $approvalRequest = ApprovalRequest::factory()->create([
        'approvable_type' => Asset::class,
        'approvable_id' => $asset->id,
    ]);

    actingAs($this->user)
        ->getJson("/api/entity-states/asset/{$asset->id}/approvals")
        ->assertOk()
        ->assertJsonPath('data.0.id', $approvalRequest->id);
});

test('returns 400 for unsupported entity type', function () {
    $asset = Asset::factory()->create();

    actingAs($this->user)
        ->getJson("/api/entity-states/unsupported/{$asset->id}/approvals")
        ->assertStatus(400)
        ->assertJson([
            'message' => 'Entity type not supported for approvals.'
        ]);
});

test('cannot access without permission', function () {
    $unprivilegedUser = createTestUserWithPermissions([]);
    $asset = Asset::factory()->create();

    actingAs($unprivilegedUser)
        ->getJson("/api/entity-states/asset/{$asset->id}/approvals")
        ->assertForbidden();
});

test('unauthenticated user cannot access endpoint', function () {
    $asset = Asset::factory()->create();

    getJson("/api/entity-states/asset/{$asset->id}/approvals")
        ->assertUnauthorized();
});
