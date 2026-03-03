<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\User;
use App\Models\Asset;
use App\Models\Permission;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('approval-monitoring');

beforeEach(function () {
    $this->user = User::factory()->create();
    
    // Create some test data
    $this->flow = ApprovalFlow::create([
        'name' => 'Test Flow',
        'code' => 'test_flow',
        'approvable_type' => Asset::class,
        'is_active' => true,
    ]);
    
    $this->step1 = ApprovalFlowStep::create([
        'approval_flow_id' => $this->flow->id,
        'step_order' => 1,
        'name' => 'Step 1',
        'approver_type' => 'user',
        'approver_user_id' => $this->user->id,
        'required_action' => 'approve',
    ]);
});

test('can render approval monitoring dashboard', function () {
    actingAs($this->user)
        ->get(route('approval-monitoring.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approval-monitoring/index')
        );
});

test('can fetch approval monitoring data', function () {
    // Create an approval request
    $request = ApprovalRequest::create([
        'approval_flow_id' => $this->flow->id,
        'approvable_type' => Asset::class,
        'approvable_id' => 1,
        'current_step_order' => 1,
        'status' => 'pending',
        'submitted_by' => $this->user->id,
        'submitted_at' => now(),
    ]);
    
    // Create pending step that is overdue
    ApprovalRequestStep::create([
        'approval_request_id' => $request->id,
        'approval_flow_step_id' => $this->step1->id,
        'step_order' => 1,
        'status' => 'pending',
        'due_at' => now()->subDay(),
    ]);
    
    // Create an approved request (completed today)
    $approvedRequest = ApprovalRequest::create([
        'approval_flow_id' => $this->flow->id,
        'approvable_type' => Asset::class,
        'approvable_id' => 2,
        'current_step_order' => 1,
        'status' => 'approved',
        'submitted_by' => $this->user->id,
        'submitted_at' => now()->subDays(2),
        'completed_at' => now(),
    ]);

    actingAs($this->user)
        ->getJson(route('api.approval-monitoring.data'))
        ->assertOk()
        ->assertJsonStructure([
            'summary' => [
                'total_pending',
                'approved_today',
                'rejected_today',
                'avg_processing_time_hours',
            ],
            'overdue_approvals' => [
                '*' => [
                    'id',
                    'request_id',
                    'document_type',
                    'submitter_name',
                    'step_name',
                    'due_at',
                    'hours_overdue',
                ]
            ],
        ])
        ->assertJsonPath('summary.total_pending', 1)
        ->assertJsonPath('summary.approved_today', 1)
        ->assertJsonPath('summary.rejected_today', 0)
        ->assertJsonPath('summary.avg_processing_time_hours', 48); // 2 days diff
        
    // Verify our single overdue request is returned
    $response = getJson(route('api.approval-monitoring.data'))->json();
    expect($response['overdue_approvals'])->toHaveCount(1);
    expect($response['overdue_approvals'][0]['document_type'])->toBe('Asset');
});

test('can filter overdue approvals by document type', function () {
    actingAs($this->user);
    // Create an approval request for PR
    $prRequest = ApprovalRequest::create([
        'approval_flow_id' => $this->flow->id,
        'approvable_type' => Asset::class,
        'approvable_id' => 1,
        'current_step_order' => 1,
        'status' => 'pending',
        'submitted_by' => $this->user->id,
        'submitted_at' => now(),
    ]);
    
    ApprovalRequestStep::create([
        'approval_request_id' => $prRequest->id,
        'approval_flow_step_id' => $this->step1->id,
        'step_order' => 1,
        'status' => 'pending',
        'due_at' => now()->subDay(),
    ]);

    // Create an approval request for another type (mock JournalEntry)
    $journalRequest = ApprovalRequest::create([
        'approval_flow_id' => $this->flow->id,
        'approvable_type' => 'App\Models\JournalEntry',
        'approvable_id' => 1,
        'current_step_order' => 1,
        'status' => 'pending',
        'submitted_by' => $this->user->id,
        'submitted_at' => now(),
    ]);
    
    ApprovalRequestStep::create([
        'approval_request_id' => $journalRequest->id,
        'approval_flow_step_id' => $this->step1->id,
        'step_order' => 1,
        'status' => 'pending',
        'due_at' => now()->subDay(),
    ]);
    
    // Request without filter
    $responseAll = getJson(route('api.approval-monitoring.data'))->json();
    expect($responseAll['overdue_approvals'])->toHaveCount(2);
    
    // Request with filter for PurchaseRequest
    $responseFilter = getJson(route('api.approval-monitoring.data', [
        'document_type' => Asset::class
    ]))->json();
    
    expect($responseFilter['overdue_approvals'])->toHaveCount(1);
    expect($responseFilter['overdue_approvals'][0]['document_type'])->toBe('Asset');
});
