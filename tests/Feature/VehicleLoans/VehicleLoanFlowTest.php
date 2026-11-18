<?php

use App\Events\ApprovalRequestApproved;
use App\Events\ApprovalRequestRejected;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\Permission;
use App\Models\VehicleLoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\CreatesTestUserWithPermissions;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class, CreatesTestUserWithPermissions::class)->group('vehicle-loans');

beforeEach(function () {
    Mail::fake();
    $this->user = $this->createTestUserWithPermissions([
        'vehicle_loan', 'vehicle_loan.pickup', 'vehicle_loan.return', 'vehicle_loan.security', 'my_approvals',
    ]);
    Sanctum::actingAs($this->user, ['*']);
});

it('creates a draft with requester and employment-derived department', function () {
    $response = postJson('/api/vehicle-loans', [
        'start_at' => now()->addDay()->toIso8601String(),
        'end_at' => now()->addDay()->addHours(2)->toIso8601String(),
        'purpose' => 'Service kendaraan dinas',
    ]);

    $response->assertCreated()->assertJsonPath('data.status', 'draft');

    $loan = VehicleLoan::firstOrFail();
    expect($loan->requester_id)->toBe($this->user->id)
        ->and($loan->code)->toStartWith('VL-');
});

it('rejects pickup when the loan is not approved', function () {
    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'status' => 'draft',
    ]);

    postJson("/api/vehicle-loans/{$loan->id}/pickups", [
        'photo_mode' => 'with_driver',
    ])->assertStatus(422);
});

it('blocks key handover without an approved approval request', function () {
    $loan = VehicleLoan::factory()->create(['status' => 'pickup_done']);
    $pickup = $loan->pickup()->create([
        'requester_id' => $this->user->id,
        'photo_mode' => 'with_driver',
        'selfie_photo_path' => 'x',
        'selfie_photo_name' => 'x.jpg',
        'picked_at' => now(),
    ]);

    $securityPermissions = collect(['vehicle_loan', 'vehicle_loan.security'])
        ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name])->id);
    $this->user->employee->permissions()->sync($securityPermissions);

    postJson("/api/vehicle-loans/pickups/{$pickup->id}/handover")
        ->assertStatus(422);
});

it('dispatches approval notifications and deduplicates them', function () {
    $approver = $this->createTestUserWithPermissions(['my_approvals']);

    $flow = ApprovalFlow::create([
        'code' => 'vehicle-loan.test',
        'name' => 'Vehicle Loan Test',
        'approvable_type' => VehicleLoan::class,
        'is_active' => true,
        'created_by' => $this->user->id,
    ]);

    ApprovalFlowStep::create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'name' => 'Approve',
        'approver_type' => 'user',
        'approver_user_id' => $approver->id,
        'required_action' => 'approve',
        'can_reject' => true,
    ]);

    config()->set('vehicle_loan.approval_flow_code', 'vehicle-loan.test');

    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'status' => 'draft',
    ]);

    postJson("/api/vehicle-loans/{$loan->id}/submit")->assertOk();

    $approval = ApprovalRequest::firstOrFail();

    expect($approval->status)->toBe('pending');
});

it('mirrors final approval status back to the vehicle loan', function () {
    $approver = $this->createTestUserWithPermissions(['my_approvals']);

    $flow = ApprovalFlow::create([
        'code' => 'vehicle-loan.mirror-test',
        'name' => 'Vehicle Loan Mirror Test',
        'approvable_type' => VehicleLoan::class,
        'is_active' => true,
        'created_by' => $this->user->id,
    ]);

    $flowStep = ApprovalFlowStep::create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'name' => 'Approve',
        'approver_type' => 'user',
        'approver_user_id' => $approver->id,
        'required_action' => 'approve',
        'can_reject' => true,
    ]);

    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'status' => 'pending',
    ]);

    $approval = ApprovalRequest::create([
        'approval_flow_id' => $flow->id,
        'approvable_type' => VehicleLoan::class,
        'approvable_id' => $loan->id,
        'current_step_order' => 1,
        'status' => 'approved',
        'submitted_by' => $this->user->id,
        'submitted_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    ApprovalRequestStep::create([
        'approval_request_id' => $approval->id,
        'approval_flow_step_id' => $flowStep->id,
        'step_order' => 1,
        'status' => 'approved',
        'acted_by' => $approver->id,
        'action' => 'approve',
        'acted_at' => now(),
    ]);

    ApprovalRequestApproved::dispatch($approval, $approver->id, true);

    $loan->refresh();

    expect($loan->status)->toBe('approved')
        ->and($loan->approved_at)->not->toBeNull()
        ->and($loan->approved_by)->toBe($approver->id);
});

it('mirrors rejection reason back to the vehicle loan', function () {
    $approver = $this->createTestUserWithPermissions(['my_approvals']);

    $flow = ApprovalFlow::create([
        'code' => 'vehicle-loan.reject-mirror-test',
        'name' => 'Vehicle Loan Reject Mirror Test',
        'approvable_type' => VehicleLoan::class,
        'is_active' => true,
        'created_by' => $this->user->id,
    ]);

    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'status' => 'in_progress',
    ]);

    $approval = ApprovalRequest::create([
        'approval_flow_id' => $flow->id,
        'approvable_type' => VehicleLoan::class,
        'approvable_id' => $loan->id,
        'current_step_order' => 1,
        'status' => 'rejected',
        'submitted_by' => $this->user->id,
        'submitted_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    ApprovalRequestRejected::dispatch($approval, $approver->id, 'Unit tidak tersedia');

    $loan->refresh();

    expect($loan->status)->toBe('rejected')
        ->and($loan->rejection_reason)->toBe('Unit tidak tersedia');
});
