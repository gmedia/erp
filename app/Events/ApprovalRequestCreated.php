<?php

namespace App\Events;

use App\Models\ApprovalRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ApprovalRequest $approvalRequest)
    {
        // Intentionally empty. Dependencies are assigned via promoted properties.
    }
}
