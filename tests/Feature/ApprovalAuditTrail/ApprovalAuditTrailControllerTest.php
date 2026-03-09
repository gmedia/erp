<?php

use App\Models\ApprovalAuditLog;
use App\Models\ApprovalRequest;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('approval-audit-trail');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['approval_audit_trail', 'approval_audit_trail.export']);
    $this->superAdmin = createTestUserWithPermissions(['super_admin']);
    $this->noPermissionUser = User::factory()->create();

    // Create some dummy data
    $this->asset = Asset::factory()->create();
    $this->approvalRequest = ApprovalRequest::factory()->create([
        'approvable_type' => Asset::class,
        'approvable_id' => $this->asset->id,
    ]);

    $this->log1 = ApprovalAuditLog::factory()->create([
        'approval_request_id' => $this->approvalRequest->id,
        'approvable_type' => Asset::class,
        'approvable_id' => $this->asset->id,
        'event' => 'submitted',
        'actor_user_id' => $this->user->id,
        'step_order' => 1,
        'created_at' => now()->subDays(2),
    ]);

    $this->log2 = ApprovalAuditLog::factory()->create([
        'approval_request_id' => $this->approvalRequest->id,
        'approvable_type' => Asset::class,
        'approvable_id' => $this->asset->id,
        'event' => 'step_approved',
        'actor_user_id' => $this->superAdmin->id,
        'step_order' => 1,
        'created_at' => now()->subDays(1),
    ]);
});

describe('index', function () {
    it('requires authentication', function () {
        get(route('approval-audit-trail.index'))->assertRedirect(route('login'));
    });

    it('denies access without permission', function () {
        actingAs($this->noPermissionUser)
            ->get(route('approval-audit-trail.index'))
            ->assertForbidden();
    });

    it('allows access with permission', function () {
        actingAs($this->user)
            ->get(route('approval-audit-trail.index'))
            ->assertOk();
    });

    it('returns json data when requested', function () {
        actingAs($this->user)
            ->getJson(route('approval-audit-trail.index'))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('data', 2)
                    ->has('meta')
                    ->has('links')
                    ->etc();
            });
    });

    it('can filter by event', function () {
        actingAs($this->user)
            ->getJson(route('approval-audit-trail.index', ['event' => 'submitted']))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('data', 1)
                    ->where('data.0.event', 'submitted')
                    ->etc();
            });
    });

    it('can filter by actor', function () {
        actingAs($this->user)
            ->getJson(route('approval-audit-trail.index', ['actor_user_id' => $this->superAdmin->id]))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('data', 1)
                    ->where('data.0.actor_user_id', $this->superAdmin->id)
                    ->etc();
            });
    });
});

describe('export', function () {
    it('requires authentication', function () {
        $this->postJson(route('api.approval-audit-trail.export'))->assertUnauthorized();
    });

    it('denies access without permission', function () {
        actingAs($this->noPermissionUser)
            ->postJson(route('api.approval-audit-trail.export'))
            ->assertForbidden();
    });

    it('allows access with permission and exports data', function () {
        $response = actingAs($this->user)
            ->postJson(route('api.approval-audit-trail.export'))
            ->assertOk()
            ->assertJsonStructure(['url', 'filename']);

        // Check file exists in storage
        $filename = $response->json('filename');
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists('exports/' . $filename);
    });
});
