<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ApprovalRequest;
use App\Models\User;

class ApprovalAuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalAuditLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null; // Spec says no updated_at

    protected $fillable = [
        'approval_request_id', 'approvable_type', 'approvable_id', 'event',
        'actor_user_id', 'step_order', 'metadata', 'ip_address', 'user_agent'
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
