<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use HasFactory;

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

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
        'conditions' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
