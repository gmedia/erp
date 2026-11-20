<?php

namespace App\Listeners;

use App\Events\ApprovalRequestApproved;
use App\Events\ApprovalRequestCreated;
use App\Events\ApprovalRequestRejected;
use App\Models\ApprovalRequest;
use App\Models\VehicleLoan;
use App\Notifiables\ApprovalEvent;
use App\Services\NotificationService;

class DispatchGenericNotification
{
    public function __construct(private readonly NotificationService $service)
    {
        // Intentionally empty. Dependencies are assigned via promoted properties.
    }

    public function handleCreated(ApprovalRequestCreated $event): void
    {
        $request = $event->approvalRequest;
        $request->loadMissing('steps.flowStep');

        $approvers = collect();

        foreach ($request->steps as $step) {
            if ($step->status !== 'pending') {
                continue;
            }

            $flowStep = $step->flowStep;

            if ($flowStep->approver_type === 'user' && $flowStep->user) {
                $approvers->put($flowStep->user->id, $flowStep->user);
            }
        }

        if ($approvers->isEmpty()) {
            return;
        }

        $this->service->send(new ApprovalEvent(
            'approval.request.created',
            $request,
            $approvers,
            [
                'title' => 'New approval request waiting for you',
                'body' => $this->approvableLabel($request) . ' needs your review.',
            ],
            '/my-approvals',
        ));
    }

    public function handleApproved(ApprovalRequestApproved $event): void
    {
        if (! $event->final) {
            return;
        }

        $request = $event->approvalRequest;
        $submitter = $request->submitter;

        $this->service->send(new ApprovalEvent(
            'approval.request.approved',
            $request,
            collect([$submitter->id => $submitter]),
            [
                'title' => 'Your request was approved',
                'body' => $this->approvableLabel($request) . ' has been approved.',
            ],
            $this->targetUrlFor($request),
        ));
    }

    public function handleRejected(ApprovalRequestRejected $event): void
    {
        $request = $event->approvalRequest;
        $submitter = $request->submitter;

        $this->service->send(new ApprovalEvent(
            'approval.request.rejected',
            $request,
            collect([$submitter->id => $submitter]),
            [
                'title' => 'Your request was rejected',
                'body' => $this->approvableLabel($request)
                    . ' was rejected. Reason: ' . $event->reason,
            ],
            $this->targetUrlFor($request),
        ));
    }

    public function handle(ApprovalRequestCreated|ApprovalRequestApproved|ApprovalRequestRejected $event): void
    {
        match (true) {
            $event instanceof ApprovalRequestCreated => $this->handleCreated($event),
            $event instanceof ApprovalRequestApproved => $this->handleApproved($event),
            $event instanceof ApprovalRequestRejected => $this->handleRejected($event),
        };
    }

    private function approvableLabel(ApprovalRequest $request): string
    {
        return class_basename($request->approvable_type) . ' #' . $request->approvable_id;
    }

    private function targetUrlFor(ApprovalRequest $request): string
    {
        if ($request->approvable_type === VehicleLoan::class) {
            return '/vehicle-loans/' . $request->approvable_id;
        }

        return '/my-approvals';
    }
}
