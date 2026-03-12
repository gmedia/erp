<?php

use App\Models\ApprovalDelegation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('approval-delegations');

describe('Approval Delegation Export API Ending', function () {
    beforeEach(function () {
        // Assume user has export permission
        $user = createTestUserWithPermissions([
            'approval_delegation',
            'approval_delegation.export',
        ]);

        actingAs($user);
    });

    test('can export approval delegations to excel', function () {
        $now = now();
        Carbon::setTestNow($now);

        ApprovalDelegation::factory()->count(3)->create();

        $response = postJson('/api/approval-delegations/export', []);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toBeString()
            ->and($data['filename'])->toContain('approval_delegation');
    });

    test('can export with search filter', function () {
        $now = now();
        Carbon::setTestNow($now);

        ApprovalDelegation::factory()->create(['reason' => 'Annual Leave']);
        ApprovalDelegation::factory()->create(['reason' => 'Business Trip']);

        $response = postJson('/api/approval-delegations/export', ['search' => 'Annual']);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toBeString();
    });

    test('can export with status filter', function () {
        $now = now();
        Carbon::setTestNow($now);

        ApprovalDelegation::factory()->create(['is_active' => true]);
        ApprovalDelegation::factory()->create(['is_active' => false]);

        $response = postJson('/api/approval-delegations/export', ['is_active' => 'true']);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toBeString();
    });
});
