<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ApprovalRequest;
use App\Models\ApprovalFlowStep;
use App\Models\User;

class ApprovalRequestStep extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalRequestStepFactory> */
    use HasFactory;

    protected $fillable = [
        'approval_request_id', 'approval_flow_step_id', 'step_order', 'status',
        'acted_by', 'delegated_from', 'action', 'comments', 'acted_at', 'due_at'
    ];

    protected $casts = [
        'acted_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function flowStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlowStep::class, 'approval_flow_step_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_from');
    }
}
