<?php

namespace App\Listeners;

use App\Events\ApprovalRequestCreated;
use App\Jobs\SendApprovalRequestNotifications as SendApprovalRequestNotificationsJob;

class SendApprovalRequestNotifications
{
    /**
     * Queue the notification job for the newly created approval request.
     */
    public function handle(ApprovalRequestCreated $event): void
    {
        SendApprovalRequestNotificationsJob::dispatch($event->approvalRequest);
    }
}
