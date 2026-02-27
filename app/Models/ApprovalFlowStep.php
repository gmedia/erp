<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalFlowStep extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalFlowStepFactory> */
    use HasFactory;

    protected $fillable = [
        'approval_flow_id',
        'step_order',
        'name',
        'approver_type',
        'approver_user_id',
        'approver_role_id',
        'approver_department_id',
        'required_action',
        'auto_approve_after_hours',
        'escalate_after_hours',
        'escalation_user_id',
        'can_reject',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'approver_user_id' => 'integer',
        'approver_role_id' => 'integer',
        'approver_department_id' => 'integer',
        'auto_approve_after_hours' => 'integer',
        'escalate_after_hours' => 'integer',
        'escalation_user_id' => 'integer',
        'can_reject' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function flow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class, 'approver_department_id');
    }

    public function escalationUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'escalation_user_id');
    }
}
