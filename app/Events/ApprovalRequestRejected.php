<?php

namespace App\Events;

use App\Models\ApprovalRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestRejected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ApprovalRequest $approvalRequest,
        public readonly int $actorUserId,
        public readonly string $reason,
    ) {}
}
