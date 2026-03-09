<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $pipeline_transition_id
 * @property string $action_type
 * @property int $execution_order
 * @property array<array-key, mixed> $config
 * @property bool $is_async
 * @property string $on_failure
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PipelineTransition $transition
 *
 * @method static \Database\Factories\PipelineTransitionActionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction whereActionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction whereExecutionOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction whereIsAsync($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction whereOnFailure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction wherePipelineTransitionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransitionAction whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PipelineTransitionAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_transition_id',
        'action_type',
        'execution_order',
        'config',
        'is_async',
        'on_failure',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_async' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function transition()
    {
        return $this->belongsTo(PipelineTransition::class, 'pipeline_transition_id');
    }
}
