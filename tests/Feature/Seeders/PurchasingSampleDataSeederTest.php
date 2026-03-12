<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalRequest;
use App\Models\PipelineTransitionAction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('sample-data-seeders');

it('keeps purchasing sample approval config and seeded approval data consistent', function () {
    seed(DatabaseSeeder::class);

    $missingApprovalFlowCodes = PipelineTransitionAction::query()
        ->where('action_type', 'trigger_approval')
        ->get()
        ->filter(function (PipelineTransitionAction $action): bool {
            $flowCode = $action->config['approval_flow_code'] ?? null;

            if (! is_string($flowCode) || $flowCode === '') {
                return false;
            }

            return ! ApprovalFlow::query()->where('code', $flowCode)->exists();
        })
        ->map(fn (PipelineTransitionAction $action) => $action->config['approval_flow_code'])
        ->values()
        ->all();

    expect($missingApprovalFlowCodes)->toBeEmpty();

    $year = now()->format('Y');

    $pendingPurchaseRequest = PurchaseRequest::query()->where('pr_number', "PR-{$year}-990002")->firstOrFail();
    $approvedPurchaseRequest = PurchaseRequest::query()->where('pr_number', "PR-{$year}-990003")->firstOrFail();
    $rejectedPurchaseRequest = PurchaseRequest::query()->where('pr_number', "PR-{$year}-990004")->firstOrFail();

    $pendingPurchaseOrder = PurchaseOrder::query()->where('po_number', "PO-{$year}-990002")->firstOrFail();
    $rejectedPurchaseOrder = PurchaseOrder::query()->where('po_number', "PO-{$year}-990004")->firstOrFail();

    $assertApprovalRequest = function (string $approvableType, int $approvableId, string $expectedStatus) {
        $approvalRequest = ApprovalRequest::query()
            ->with('steps')
            ->where('approvable_type', $approvableType)
            ->where('approvable_id', $approvableId)
            ->first();

        expect($approvalRequest)->not->toBeNull();
        expect($approvalRequest->status)->toBe($expectedStatus);
        expect($approvalRequest->steps)->not->toBeEmpty();

        return $approvalRequest;
    };

    $pendingRequestApproval = $assertApprovalRequest(PurchaseRequest::class, $pendingPurchaseRequest->id, 'pending');
    $approvedRequestApproval = $assertApprovalRequest(PurchaseRequest::class, $approvedPurchaseRequest->id, 'approved');
    $rejectedRequestApproval = $assertApprovalRequest(PurchaseRequest::class, $rejectedPurchaseRequest->id, 'rejected');

    $pendingOrderApproval = $assertApprovalRequest(PurchaseOrder::class, $pendingPurchaseOrder->id, 'pending');
    $rejectedOrderApproval = $assertApprovalRequest(PurchaseOrder::class, $rejectedPurchaseOrder->id, 'rejected');

    expect($pendingRequestApproval->steps->every(fn ($step) => $step->status === 'pending'))->toBeTrue();
    expect($approvedRequestApproval->steps->every(fn ($step) => $step->status === 'approved'))->toBeTrue();
    expect($rejectedRequestApproval->steps->contains(fn ($step) => $step->status === 'rejected'))->toBeTrue();

    expect($pendingOrderApproval->steps->every(fn ($step) => $step->status === 'pending'))->toBeTrue();
    expect($rejectedOrderApproval->steps->contains(fn ($step) => $step->status === 'rejected'))->toBeTrue();
});
