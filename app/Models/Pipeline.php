<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $entity_type
 * @property string|null $description
 * @property int $version
 * @property bool $is_active
 * @property array<array-key, mixed>|null $conditions
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PipelineEntityState> $entityStates
 * @property-read int|null $entity_states_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PipelineState> $states
 * @property-read int|null $states_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PipelineTransition> $transitions
 * @property-read int|null $transitions_count
 *
 * @method static \Database\Factories\PipelineFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline whereConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline whereEntityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pipeline whereVersion($value)
 *
 * @mixin \Eloquent
 */
class Pipeline extends Model
{
    /** @use HasFactory<\Database\Factories\PipelineFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'entity_type',
        'description',
        'version',
        'is_active',
        'conditions',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
        'conditions' => 'json',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function states(): HasMany
    {
        return $this->hasMany(PipelineState::class)->orderBy('sort_order', 'asc');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(PipelineTransition::class)->orderBy('sort_order', 'asc');
    }

    public function entityStates(): HasMany
    {
        return $this->hasMany(PipelineEntityState::class);
    }
}
