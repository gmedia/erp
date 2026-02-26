<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PipelineStateLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Logs shouldn't be updated

    protected $fillable = [
        'pipeline_entity_state_id',
        'entity_type',
        'entity_id',
        'from_state_id',
        'to_state_id',
        'transition_id',
        'performed_by',
        'comment',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function pipelineEntityState(): BelongsTo
    {
        return $this->belongsTo(PipelineEntityState::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function fromState(): BelongsTo
    {
        return $this->belongsTo(PipelineState::class, 'from_state_id');
    }

    public function toState(): BelongsTo
    {
        return $this->belongsTo(PipelineState::class, 'to_state_id');
    }

    public function transition(): BelongsTo
    {
        return $this->belongsTo(PipelineTransition::class, 'transition_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
