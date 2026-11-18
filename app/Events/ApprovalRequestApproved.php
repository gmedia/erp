<?php

namespace App\Events;

use App\Models\ApprovalRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestApproved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ApprovalRequest $approvalRequest,
        public readonly int $actorUserId,
        public readonly bool $final,
    ) {}
}
