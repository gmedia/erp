<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PipelineEntityState extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'entity_type',
        'entity_id',
        'current_state_id',
        'last_transitioned_by',
        'last_transitioned_at',
        'metadata',
    ];

    protected $casts = [
        'last_transitioned_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function currentState(): BelongsTo
    {
        return $this->belongsTo(PipelineState::class, 'current_state_id');
    }

    public function lastTransitionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_transitioned_by');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PipelineStateLog::class)->orderBy('created_at', 'desc');
    }
}
