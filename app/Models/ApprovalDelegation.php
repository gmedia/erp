<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $delegator_user_id
 * @property int $delegate_user_id
 * @property string|null $approvable_type
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string|null $reason
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $delegate
 * @property-read \App\Models\User $delegator
 *
 * @method static \Database\Factories\ApprovalDelegationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation whereApprovableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation whereDelegateUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation whereDelegatorUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDelegation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ApprovalDelegation extends Model
{
    use HasFactory;

    protected $fillable = [
        'delegator_user_id',
        'delegate_user_id',
        'approvable_type',
        'start_date',
        'end_date',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who delegated their approval rights.
     */
    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegator_user_id');
    }

    /**
     * Get the user to whom the approval rights are delegated.
     */
    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_user_id');
    }
}
