<?php

namespace App\Models;

use Database\Factories\PipelineStateLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pipeline_entity_state_id
 * @property string $entity_type
 * @property int $entity_id
 * @property int|null $from_state_id
 * @property int $to_state_id
 * @property int|null $transition_id
 * @property int|null $performed_by
 * @property string|null $comment
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property-read Model|Eloquent $entity
 * @property-read PipelineState|null $fromState
 * @property-read User|null $performedBy
 * @property-read PipelineEntityState $pipelineEntityState
 * @property-read PipelineState $toState
 * @property-read PipelineTransition|null $transition
 *
 * @method static \Database\Factories\PipelineStateLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog whereEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog whereEntityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog whereFromStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog wherePerformedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog wherePipelineEntityStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog whereToStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog whereTransitionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineStateLog whereUserAgent($value)
 *
 * @mixin \Eloquent
 */
class PipelineStateLog extends Model
{
    /** @use HasFactory<PipelineStateLogFactory> */
    use HasFactory;

    const UPDATED_AT = null; // Logs shouldn't be updated

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
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
