<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalFlow extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalFlowFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'approvable_type',
        'description',
        'is_active',
        'conditions',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conditions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function steps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ApprovalFlowStep::class)->orderBy('step_order');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
