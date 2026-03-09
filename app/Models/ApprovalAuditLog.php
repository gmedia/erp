<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int|null $approval_request_id
 * @property string $approvable_type
 * @property int $approvable_id
 * @property string $event
 * @property int|null $actor_user_id
 * @property int|null $step_order
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read User|null $actor
 * @property-read Model|Eloquent $approvable
 * @property-read ApprovalRequest|null $request
 *
 * @method static \Database\Factories\ApprovalAuditLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog whereActorUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog whereApprovableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog whereApprovableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog whereApprovalRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog whereStepOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAuditLog whereUserAgent($value)
 *
 * @mixin \Eloquent
 */
class ApprovalAuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalAuditLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null; // Spec says no updated_at

    protected $fillable = [
        'approval_request_id', 'approvable_type', 'approvable_id', 'event',
        'actor_user_id', 'step_order', 'metadata', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
