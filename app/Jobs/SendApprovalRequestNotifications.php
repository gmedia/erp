<?php

namespace App\Jobs;

use App\Mail\ApprovalRequestNotification;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendApprovalRequestNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly ApprovalRequest $approvalRequest)
    {
        // Intentionally empty. Dependencies are assigned via promoted properties.
    }

    public function handle(): void
    {
        $this->approvalRequest->loadMissing('steps.flowStep.user');

        $this->approvalRequest->steps
            ->filter(fn ($step) => $step->status === 'pending')
            ->map(fn ($step) => $step->flowStep)
            ->filter(fn (?ApprovalFlowStep $flowStep) => $flowStep?->approver_type === 'user')
            ->each(function (ApprovalFlowStep $flowStep) {
                $approver = $flowStep->user;

                if ($approver?->email) {
                    $mail = new ApprovalRequestNotification($this->approvalRequest, $approver);
                    Mail::to($approver->email)->send($mail);
                }
            });
    }
}
