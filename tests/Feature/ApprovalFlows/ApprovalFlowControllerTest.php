<?php

use App\Models\ApprovalFlow;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('approval-flows');

describe('Approval Flow API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'approval_flow',
            'approval_flow.create',
            'approval_flow.edit',
            'approval_flow.delete',
        ]);
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
    });

    test('index returns paginated approval flows with proper meta structure', function () {
        ApprovalFlow::factory()->count(25)->create();

        $response = getJson('/api/approval-flows?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'approvable_type',
                        'description',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        expect($response->json('meta.total'))->toBe(25)
            ->and($response->json('meta.per_page'))->toBe(10)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering by name and code', function () {
        ApprovalFlow::factory()->create(['name' => 'Standard Asset Request', 'code' => 'REQ-01']);
        ApprovalFlow::factory()->create(['name' => 'Urgent Request', 'code' => 'REQ-URG']);
        ApprovalFlow::factory()->create(['name' => 'Normal Approval', 'code' => 'NML-01']);

        // Search by name
        $response = getJson('/api/approval-flows?search=Standard');
        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1);
        
        // Search by code
        $response = getJson('/api/approval-flows?search=REQ-');
        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);
    });

    test('index filters by approvable type', function () {
        ApprovalFlow::factory()->count(2)->create(['approvable_type' => 'AssetMovement']);
        ApprovalFlow::factory()->create(['approvable_type' => 'PurchaseRequest']);

        $response = getJson('/api/approval-flows?approvable_type=AssetMovement');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);
    });

    test('index supports filtering by status', function () {
        ApprovalFlow::factory()->create(['is_active' => true]);
        ApprovalFlow::factory()->create(['is_active' => false]);
        ApprovalFlow::factory()->create(['is_active' => true]);

        $response = getJson('/api/approval-flows?is_active=1');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);
    });

    test('index supports sorting by different fields', function () {
        ApprovalFlow::factory()->create(['name' => 'Z Flow']);
        ApprovalFlow::factory()->create(['name' => 'A Flow']);

        $response = getJson('/api/approval-flows?sort_by=name&sort_direction=asc');

        $response->assertOk();

        $data = $response->json('data');
        $names = array_column($data, 'name');
        $aIndex = array_search('A Flow', $names);
        $zIndex = array_search('Z Flow', $names);
        expect($aIndex)->toBeLessThan($zIndex);
    });

    test('store creates approval flow with valid data and returns 201 status', function () {
        $department = Department::factory()->create();
        $approver = User::factory()->create();

        $payload = [
            'name' => 'Standard Asset Request',
            'code' => 'std_asset_req',
            'approvable_type' => 'App\\Models\\AssetMovement',
            'description' => 'Test flow',
            'is_active' => true,
            'conditions' => null,
            'steps' => [
                [
                    'name' => 'Manager Approval',
                    'approver_type' => 'user',
                    'approver_user_id' => $approver->id,
                    'required_action' => 'approve',
                    'auto_approve_after_hours' => null,
                    'escalate_after_hours' => null,
                    'escalation_user_id' => null,
                    'can_reject' => true,
                ],
                [
                    'name' => 'Dept Head Review',
                    'approver_type' => 'department_head',
                    'approver_department_id' => $department->id,
                    'required_action' => 'review',
                    'auto_approve_after_hours' => 24,
                    'escalate_after_hours' => null,
                    'escalation_user_id' => null,
                    'can_reject' => false,
                ]
            ]
        ];

        $response = postJson('/api/approval-flows', $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'code',
                    'approvable_type',
                    'description',
                    'is_active',
                    'steps',
                ]
            ])
            ->assertJsonFragment([
                'name' => 'Standard Asset Request',
                'code' => 'std_asset_req',
            ]);

        expect($response->json('data.steps'))->toHaveCount(2);

        assertDatabaseHas('approval_flows', [
            'code' => 'std_asset_req',
        ]);

        assertDatabaseHas('approval_flow_steps', [
            'name' => 'Manager Approval',
            'approver_user_id' => $approver->id,
        ]);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/approval-flows', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'code',
                'approvable_type'
            ]);
    });

    test('store validates unique code constraint', function () {
        ApprovalFlow::factory()->create(['code' => 'EXISTING_CODE']);

        $response = postJson('/api/approval-flows', [
            'name' => 'New Flow',
            'code' => 'EXISTING_CODE',
            'approvable_type' => 'App\\Models\\AssetMovement',
            'is_active' => true,
            'steps' => [
                [
                    'name' => 'Manager Approval',
                    'approver_type' => 'user',
                    'approver_user_id' => User::factory()->create()->id,
                    'required_action' => 'approve',
                    'can_reject' => true,
                ]
            ]
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    });

    test('show returns single approval flow with steps', function () {
        $flow = ApprovalFlow::factory()->create();
        $flow->steps()->create([
            'step_order' => 1,
            'name' => 'Manager Approval',
            'approver_type' => 'user',
            'approver_user_id' => User::factory()->create()->id,
            'required_action' => 'approve',
            'can_reject' => true,
        ]);

        $response = getJson("/api/approval-flows/{$flow->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'code',
                    'approvable_type',
                    'steps',
                ]
            ])
            ->assertJsonFragment([
                'id' => $flow->id,
                'name' => $flow->name,
            ]);
            
        expect($response->json('data.steps'))->toHaveCount(1);
    });

    test('show returns 404 for non-existent approval flow', function () {
        $response = getJson('/api/approval-flows/99999');

        $response->assertNotFound();
    });

    test('update modifies approval flow and replaces steps', function () {
        $user = User::factory()->create();
        $flow = ApprovalFlow::factory()->create(['name' => 'Old Name']);
        $flow->steps()->create([
            'step_order' => 1,
            'name' => 'Old Step',
            'approver_type' => 'user',
            'approver_user_id' => $user->id,
            'required_action' => 'approve',
            'can_reject' => true,
        ]);

        $payload = [
            'name' => 'New Name',
            'code' => $flow->code,
            'approvable_type' => $flow->approvable_type,
            'is_active' => true,
            'steps' => [
                [
                    'name' => 'New Step 1',
                    'approver_type' => 'role',
                    'approver_role_id' => 1,
                    'required_action' => 'review',
                    'can_reject' => false,
                ]
            ]
        ];

        $response = putJson("/api/approval-flows/{$flow->id}", $payload);

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'New Name',
            ]);

        $flow->refresh();
        expect($flow->name)->toBe('New Name');
        expect($flow->steps)->toHaveCount(1);
        expect($flow->steps->first()->name)->toBe('New Step 1');
        
        assertDatabaseMissing('approval_flow_steps', [
            'name' => 'Old Step',
        ]);
    });

    test('update ignores unique code validation for same flow', function () {
        $flow = ApprovalFlow::factory()->create(['code' => 'EXISTING_CODE']);

        $response = putJson("/api/approval-flows/{$flow->id}", [
            'name' => 'Updated Name',
            'code' => 'EXISTING_CODE', // Same code should be allowed
            'approvable_type' => $flow->approvable_type,
            'is_active' => true,
            'steps' => [
                [
                    'name' => 'Step 1',
                    'approver_type' => 'user',
                    'approver_user_id' => User::factory()->create()->id,
                    'required_action' => 'approve',
                    'can_reject' => true,
                ]
            ]
        ]);

        $response->assertOk();
    });

    test('update returns 404 for non-existent approval flow', function () {
        $response = putJson('/api/approval-flows/99999', [
            'name' => 'New Flow',
            'code' => 'CODE',
            'approvable_type' => 'App\\Models\\AssetMovement',
            'is_active' => true,
            'steps' => [
                [
                    'name' => 'Manager Approval',
                    'approver_type' => 'user',
                    'approver_user_id' => User::factory()->create()->id,
                    'required_action' => 'approve',
                    'can_reject' => true,
                ]
            ]
        ]);

        $response->assertNotFound();
    });

    test('destroy removes approval flow and returns 204 status', function () {
        $flow = ApprovalFlow::factory()->create();

        $response = deleteJson("/api/approval-flows/{$flow->id}");

        $response->assertNoContent();

        assertDatabaseMissing('approval_flows', ['id' => $flow->id]);
    });

    test('destroy returns 404 for non-existent approval flow', function () {
        $response = deleteJson('/api/approval-flows/99999');

        $response->assertNotFound();
    });
});

describe('Approval Flow API Permission Tests', function () {
    test('store returns 403 when user lacks approval_flow.create permission', function () {
        $user = createTestUserWithPermissions(['approval_flow']);
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        $response = postJson('/api/approval-flows', [
            'name' => 'New Flow',
            'code' => 'CODE',
            'approvable_type' => 'App\\Models\\AssetMovement',
            'is_active' => true,
            'steps' => []
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('update returns 403 when user lacks approval_flow.edit permission', function () {
        $user = createTestUserWithPermissions(['approval_flow']);
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        $flow = ApprovalFlow::factory()->create();

        $response = putJson("/api/approval-flows/{$flow->id}", [
            'name' => 'New Flow',
            'code' => 'CODE',
            'approvable_type' => 'App\\Models\\AssetMovement',
            'is_active' => true,
            'steps' => []
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('destroy returns 403 when user lacks approval_flow.delete permission', function () {
        $user = createTestUserWithPermissions(['approval_flow']);
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        $flow = ApprovalFlow::factory()->create();

        $response = deleteJson("/api/approval-flows/{$flow->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });
});
