<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $pipeline_id
 * @property int $from_state_id
 * @property int $to_state_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string|null $required_permission
 * @property array<array-key, mixed>|null $guard_conditions
 * @property bool $requires_confirmation
 * @property bool $requires_comment
 * @property bool $requires_approval
 * @property int $sort_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PipelineTransitionAction> $actions
 * @property-read int|null $actions_count
 * @property-read \App\Models\PipelineState $fromState
 * @property-read \App\Models\Pipeline $pipeline
 * @property-read \App\Models\PipelineState $toState
 *
 * @method static \Database\Factories\PipelineTransitionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereFromStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereGuardConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition wherePipelineId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereRequiredPermission($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereRequiresApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereRequiresComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereRequiresConfirmation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereToStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PipelineTransition whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PipelineTransition extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'from_state_id',
        'to_state_id',
        'name',
        'code',
        'description',
        'required_permission',
        'guard_conditions',
        'requires_confirmation',
        'requires_comment',
        'requires_approval',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'guard_conditions' => 'array',
        'requires_confirmation' => 'boolean',
        'requires_comment' => 'boolean',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function fromState(): BelongsTo
    {
        return $this->belongsTo(PipelineState::class, 'from_state_id');
    }

    public function toState(): BelongsTo
    {
        return $this->belongsTo(PipelineState::class, 'to_state_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(PipelineTransitionAction::class)->orderBy('execution_order');
    }
}
