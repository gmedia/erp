<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $pipeline_id
 * @property string $entity_type
 * @property int $entity_id
 * @property int $current_state_id
 * @property int|null $last_transitioned_by
 * @property \Illuminate\Support\Carbon|null $last_transitioned_at
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PipelineState $currentState
 * @property-read Model|Eloquent $entity
 * @property-read \App\Models\User|null $lastTransitionedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PipelineStateLog> $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Pipeline $pipeline
 *
 * @method static \Database\Factories\PipelineEntityStateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState whereCurrentStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState whereEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState whereEntityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState whereLastTransitionedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState whereLastTransitionedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState wherePipelineId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineEntityState whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
