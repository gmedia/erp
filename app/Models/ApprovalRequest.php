<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $approval_flow_id
 * @property string $approvable_type
 * @property int $approvable_id
 * @property int $current_step_order
 * @property string $status
 * @property int $submitted_by
 * @property \Illuminate\Support\Carbon $submitted_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model $approvable
 * @property-read \App\Models\ApprovalFlow|null $flow
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApprovalRequestStep> $steps
 * @property-read int|null $steps_count
 * @property-read \App\Models\User $submitter
 *
 * @method static \Database\Factories\ApprovalRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereApprovableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereApprovableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereApprovalFlowId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereCurrentStepOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereSubmittedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ApprovalRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'approval_flow_id', 'approvable_type', 'approvable_id', 'current_step_order',
        'status', 'submitted_by', 'submitted_at', 'completed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalRequestStep::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
