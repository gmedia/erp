<?php

namespace App\Models;

use Database\Factories\ApprovalDelegationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $delegator_user_id
 * @property int $delegate_user_id
 * @property string|null $approvable_type
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string|null $reason
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $delegate
 * @property-read User $delegator
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
    /** @use HasFactory<ApprovalDelegationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'delegator_user_id',
        'delegate_user_id',
        'approvable_type',
        'start_date',
        'end_date',
        'reason',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
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
