<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('approval-monitoring');

/**
 * @param  array<string>  $permissions
 */
function makeApprovalUser(?int $branchId, array $permissions): User
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
    ]);
    Employment::factory()->create([
        'employee_id' => $employee->id,
        'branch_id' => $branchId,
        'department_id' => null,
        'position_id' => null,
    ]);

    $ids = [];
    foreach ($permissions as $name) {
        $ids[] = Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => ucwords(str_replace(['.', '-'], ' ', $name))],
        )->id;
    }
    $employee->permissions()->sync($ids);

    return $user;
}

beforeEach(function () {
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();

    $this->flow = ApprovalFlow::create([
        'name' => 'Test Flow',
        'code' => 'test_flow',
        'approvable_type' => Asset::class,
        'is_active' => true,
    ]);

    $this->step = ApprovalFlowStep::create([
        'approval_flow_id' => $this->flow->id,
        'step_order' => 1,
        'name' => 'Step 1',
        'approver_type' => 'user',
        'required_action' => 'approve',
    ]);
});

function seedPendingRequest(int $flowId, int $stepId, int $submittedBy, int $approvableId, ?int $branchId, bool $overdue = true): ApprovalRequest
{
    $request = ApprovalRequest::create([
        'approval_flow_id' => $flowId,
        'approvable_type' => Asset::class,
        'approvable_id' => $approvableId,
        'branch_id' => $branchId,
        'current_step_order' => 1,
        'status' => 'pending',
        'submitted_by' => $submittedBy,
        'submitted_at' => now(),
    ]);

    ApprovalRequestStep::create([
        'approval_request_id' => $request->id,
        'approval_flow_step_id' => $stepId,
        'step_order' => 1,
        'status' => 'pending',
        'due_at' => $overdue ? now()->subDay() : now()->addDay(),
    ]);

    return $request;
}

it('scopes pending count to the selected branch for a view_all_branches user', function () {
    $admin = makeApprovalUser(null, ['approval_monitoring', 'view_all_branches']);
    seedPendingRequest($this->flow->id, $this->step->id, $admin->id, 1, $this->branchA->id);
    seedPendingRequest($this->flow->id, $this->step->id, $admin->id, 2, $this->branchA->id);
    seedPendingRequest($this->flow->id, $this->step->id, $admin->id, 3, $this->branchB->id);
    seedPendingRequest($this->flow->id, $this->step->id, $admin->id, 4, null);

    Sanctum::actingAs($admin, ['*']);
    $response = getJson("/api/approval-monitoring/data?branch_id={$this->branchA->id}");

    $response->assertOk();
    expect($response->json('summary.total_pending'))->toBe(2);
});

it('scopes overdue approvals to the selected branch via the request relation', function () {
    $admin = makeApprovalUser(null, ['approval_monitoring', 'view_all_branches']);
    $reqA = seedPendingRequest($this->flow->id, $this->step->id, $admin->id, 1, $this->branchA->id);
    seedPendingRequest($this->flow->id, $this->step->id, $admin->id, 2, $this->branchB->id);

    Sanctum::actingAs($admin, ['*']);
    $response = getJson("/api/approval-monitoring/data?branch_id={$this->branchA->id}");

    $overdue = $response->json('overdue_approvals');
    expect($overdue)->toHaveCount(1)
        ->and($overdue[0]['request_id'])->toBe($reqA->id);
});

it('excludes null-branch requests when a branch is selected', function () {
    $admin = makeApprovalUser(null, ['approval_monitoring', 'view_all_branches']);
    seedPendingRequest($this->flow->id, $this->step->id, $admin->id, 1, $this->branchA->id);
    seedPendingRequest($this->flow->id, $this->step->id, $admin->id, 2, null);

    Sanctum::actingAs($admin, ['*']);
    $response = getJson("/api/approval-monitoring/data?branch_id={$this->branchA->id}");

    expect($response->json('summary.total_pending'))->toBe(1)
        ->and($response->json('overdue_approvals'))->toHaveCount(1);
});

it('shows all requests when no branch is selected', function () {
    $admin = makeApprovalUser(null, ['approval_monitoring', 'view_all_branches']);
    seedPendingRequest($this->flow->id, $this->step->id, $admin->id, 1, $this->branchA->id);
    seedPendingRequest($this->flow->id, $this->step->id, $admin->id, 2, $this->branchB->id);
    seedPendingRequest($this->flow->id, $this->step->id, $admin->id, 3, null);

    Sanctum::actingAs($admin, ['*']);
    $response = getJson('/api/approval-monitoring/data');

    expect($response->json('summary.total_pending'))->toBe(3);
});

it('forces a branch-scoped employee to their own branch regardless of requested branch_id', function () {
    $employee = makeApprovalUser($this->branchA->id, ['approval_monitoring']);
    seedPendingRequest($this->flow->id, $this->step->id, $employee->id, 1, $this->branchA->id);
    seedPendingRequest($this->flow->id, $this->step->id, $employee->id, 2, $this->branchB->id);

    Sanctum::actingAs($employee, ['*']);
    $response = getJson("/api/approval-monitoring/data?branch_id={$this->branchB->id}");

    expect($response->json('summary.total_pending'))->toBe(1)
        ->and($response->json('overdue_approvals'))->toHaveCount(1);
});
