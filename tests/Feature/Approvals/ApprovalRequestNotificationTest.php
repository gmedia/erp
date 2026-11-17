<?php

use App\Actions\Approvals\TriggerApprovalAction;
use App\Mail\ApprovalRequestNotification;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('approvals');

test('approval request creation queues an email notification to the first approver', function () {
    Mail::fake();

    $submitter = User::factory()->create();
    $approver = User::factory()->create();
    Sanctum::actingAs($submitter, ['*']);

    $flow = ApprovalFlow::factory()->create([
        'approvable_type' => PurchaseRequest::class,
        'code' => 'PR-MAIL-TEST',
        'is_active' => true,
        'conditions' => null,
    ]);

    ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'approver_type' => 'user',
        'approver_user_id' => $approver->id,
    ]);

    $entity = PurchaseRequest::factory()->create();

    $request = app(TriggerApprovalAction::class)->execute($entity, ['flow_code' => 'PR-MAIL-TEST']);

    expect($request)->not->toBeNull();

    Mail::assertSent(ApprovalRequestNotification::class, function (ApprovalRequestNotification $mail) use ($approver, $request) {
        return $mail->hasTo($approver->email)
            && $mail->approver->is($approver)
            && $mail->approvalRequest->is($request);
    });
});

test('approval request with no user approver sends no email', function () {
    Mail::fake();

    $submitter = User::factory()->create();
    Sanctum::actingAs($submitter, ['*']);

    $flow = ApprovalFlow::factory()->create([
        'approvable_type' => PurchaseRequest::class,
        'code' => 'PR-NO-APPROVER',
        'is_active' => true,
        'conditions' => null,
    ]);

    ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'approver_type' => 'role',
        'approver_user_id' => null,
    ]);

    app(TriggerApprovalAction::class)->execute(PurchaseRequest::factory()->create(), ['flow_code' => 'PR-NO-APPROVER']);

    Mail::assertNothingSent();
});
