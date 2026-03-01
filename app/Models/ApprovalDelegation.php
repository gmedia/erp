<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
