<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipelineTransitionAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_transition_id',
        'action_type',
        'execution_order',
        'config',
        'is_async',
        'on_failure',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_async' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function transition()
    {
        return $this->belongsTo(PipelineTransition::class, 'pipeline_transition_id');
    }
}
