<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
