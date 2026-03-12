<?php

use App\Models\ApprovalDelegation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('approval-delegations');

describe('Approval Delegation API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'approval_delegation',
            'approval_delegation.create',
            'approval_delegation.edit',
            'approval_delegation.delete',
        ]);
        Sanctum::actingAs($user, ['*']);
    });

    test('index returns paginated delegations', function () {
        $baseline = ApprovalDelegation::count();
        ApprovalDelegation::factory()->count(15)->create();

        $response = getJson('/api/approval-delegations?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'per_page', 'current_page'],
            ]);

        expect($response->json('meta.total'))->toBe($baseline + 15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering', function () {
        ApprovalDelegation::factory()->create(['reason' => 'Vacaation Leave']);
        ApprovalDelegation::factory()->create(['reason' => 'Business Trip']);

        $response = getJson('/api/approval-delegations?search=Vacaation');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.reason'))->toBe('Vacaation Leave');
    });

    test('index supports filtering by delegator', function () {
        $delegator1 = User::factory()->create(['name' => 'John Doe']);
        $delegator2 = User::factory()->create(['name' => 'Jane Smith']);

        ApprovalDelegation::factory()->create(['delegator_user_id' => $delegator1->id]);
        ApprovalDelegation::factory()->create(['delegator_user_id' => $delegator2->id]);

        $response = getJson('/api/approval-delegations?delegator_user_id=' . $delegator1->id);

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.delegator.id'))->toBe($delegator1->id);
    });

    test('index supports filtering by status', function () {
        $baselineActive = ApprovalDelegation::where('is_active', true)->count();

        ApprovalDelegation::factory()->create(['is_active' => true]);
        ApprovalDelegation::factory()->create(['is_active' => false]);

        $response = getJson('/api/approval-delegations?is_active=true');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount($baselineActive + 1)
            ->and($response->json('data.0.is_active'))->toBe(true);
    });

    test('store creates approval delegation', function () {
        $delegator = User::factory()->create();
        $delegate = User::factory()->create();
        $data = [
            'delegator_user_id' => $delegator->id,
            'delegate_user_id' => $delegate->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-10',
            'reason' => 'Test reason',
            'is_active' => true,
        ];

        $response = postJson('/api/approval-delegations', $data);

        $response->assertCreated()
            ->assertJsonFragment(['reason' => 'Test reason']);

        assertDatabaseHas('approval_delegations', ['reason' => 'Test reason']);
    });

    test('store validates delegate cannot be delegator', function () {
        $user = User::factory()->create();

        $response = postJson('/api/approval-delegations', [
            'delegator_user_id' => $user->id,
            'delegate_user_id' => $user->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-10',
            'is_active' => true,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['delegate_user_id']);
    });

    test('update modifies approval delegation', function () {
        $delegation = ApprovalDelegation::factory()->create();

        $data = [
            'reason' => 'Updated reason',
            'is_active' => false,
        ];

        $response = putJson("/api/approval-delegations/{$delegation->id}", $data);

        $response->assertOk()
            ->assertJsonFragment(['reason' => 'Updated reason']);

        $delegation->refresh();
        expect($delegation->reason)->toBe('Updated reason')
            ->and($delegation->is_active)->toBe(false);
    });

    test('destroy removes approval delegation', function () {
        $delegation = ApprovalDelegation::factory()->create();

        $response = deleteJson("/api/approval-delegations/{$delegation->id}");

        $response->assertNoContent();
        assertDatabaseMissing('approval_delegations', ['id' => $delegation->id]);
    });
});
