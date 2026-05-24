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
 * @property string $approvable_type
 * @property string|null $description
 * @property bool $is_active
 * @property array<array-key, mixed>|null $conditions
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApprovalFlowStep> $steps
 * @property-read int|null $steps_count
 *
 * @method static \Database\Factories\ApprovalFlowFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow whereApprovableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow whereConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ApprovalFlow extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalFlowFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'approvable_type',
        'description',
        'is_active',
        'conditions',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'conditions' => 'array',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalFlowStep::class)->orderBy('step_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
