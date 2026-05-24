<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $approval_flow_id
 * @property int $step_order
 * @property string $name
 * @property string $approver_type
 * @property int|null $approver_user_id
 * @property int|null $approver_role_id
 * @property int|null $approver_department_id
 * @property string $required_action
 * @property int|null $auto_approve_after_hours
 * @property int|null $escalate_after_hours
 * @property int|null $escalation_user_id
 * @property bool $can_reject
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\User|null $escalationUser
 * @property-read \App\Models\ApprovalFlow $flow
 * @property-read \App\Models\User|null $user
 *
 * @method static \Database\Factories\ApprovalFlowStepFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereApprovalFlowId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereApproverDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereApproverRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereApproverType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereApproverUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereAutoApproveAfterHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereCanReject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereEscalateAfterHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereEscalationUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereRequiredAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereStepOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlowStep whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ApprovalFlowStep extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalFlowStepFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'step_order' => 'integer',
        'approver_user_id' => 'integer',
        'approver_role_id' => 'integer',
        'approver_department_id' => 'integer',
        'auto_approve_after_hours' => 'integer',
        'escalate_after_hours' => 'integer',
        'escalation_user_id' => 'integer',
        'can_reject' => 'boolean',
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
