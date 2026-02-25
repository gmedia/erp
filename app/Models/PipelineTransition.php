<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function fromState()
    {
        return $this->belongsTo(PipelineState::class, 'from_state_id');
    }

    public function toState()
    {
        return $this->belongsTo(PipelineState::class, 'to_state_id');
    }

    public function actions()
    {
        return $this->hasMany(PipelineTransitionAction::class)->orderBy('execution_order');
    }
}
