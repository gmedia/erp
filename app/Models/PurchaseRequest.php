<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $pr_number
 * @property int $branch_id
 * @property int|null $department_id
 * @property int|null $requested_by
 * @property \Illuminate\Support\Carbon $request_date
 * @property \Illuminate\Support\Carbon|null $required_date
 * @property string $priority
 * @property string $status
 * @property numeric|null $estimated_amount
 * @property string|null $notes
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseRequestItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Employee|null $requester
 * @method static \Database\Factories\PurchaseRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereEstimatedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest wherePrNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereRequestDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereRequiredDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PurchaseRequest extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseRequestFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'pr_number',
        'branch_id',
        'department_id',
        'requested_by',
        'request_date',
        'required_date',
        'priority',
        'status',
        'estimated_amount',
        'notes',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'request_date' => 'date',
        'required_date' => 'date',
        'estimated_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }
}
