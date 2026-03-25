<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $approval_request_id
 * @property int $approval_flow_step_id
 * @property int $step_order
 * @property string $status
 * @property int|null $acted_by
 * @property int|null $delegated_from
 * @property string|null $action
 * @property string|null $comments
 * @property \Illuminate\Support\Carbon|null $acted_at
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $actor
 * @property-read User|null $delegator
 * @property-read ApprovalFlowStep $flowStep
 * @property-read ApprovalRequest $request
 *
 * @method static \Database\Factories\ApprovalRequestStepFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereActedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereActedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereApprovalFlowStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereApprovalRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereDelegatedFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereDueAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereStepOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ApprovalRequestStep extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalRequestStepFactory> */
    use HasFactory;

    protected $fillable = [
        'approval_request_id', 'approval_flow_step_id', 'step_order', 'status',
        'acted_by', 'delegated_from', 'action', 'comments', 'acted_at', 'due_at',
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

    public function scopeAssignedToUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('flowStep', function (Builder $flowStepQuery) use ($userId) {
            $flowStepQuery->where('approver_type', 'user')
                ->where('approver_user_id', $userId);
        });
    }

    public function scopeForActiveRequests(Builder $query): Builder
    {
        return $query->whereHas('request', function (Builder $requestQuery) {
            $requestQuery->whereIn('status', ['pending', 'in_progress']);
        });
    }

    public function scopeCurrentRequestStep(Builder $query): Builder
    {
        return $query->whereHas('request', function (Builder $requestQuery) {
            $requestQuery->whereColumn(
                'approval_requests.current_step_order',
                'approval_request_steps.step_order',
            );
        });
    }

    public function scopePendingInboxForUser(Builder $query, int $userId): Builder
    {
        return $query->where('status', 'pending')
            ->whereHas('request', function (Builder $requestQuery) {
                $requestQuery->whereIn('status', ['pending', 'in_progress']);
            })
            ->whereHas('request', function (Builder $requestQuery) {
                $requestQuery->whereColumn(
                    'approval_requests.current_step_order',
                    'approval_request_steps.step_order',
                );
            })
            ->whereHas('flowStep', function (Builder $flowStepQuery) use ($userId) {
                $flowStepQuery->where('approver_type', 'user')
                    ->where('approver_user_id', $userId);
            });
    }
}
