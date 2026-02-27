<?php

namespace App\Traits;

use App\Models\PipelineEntityState;
use App\Models\PipelineStateLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasPipeline
{
    /**
     * Get the entity's active pipeline state.
     */
    public function pipelineEntityState(): MorphOne
    {
        return $this->morphOne(PipelineEntityState::class, 'entity');
    }

    /**
     * Get all pipeline states for this entity (historical if pipeline changes).
     */
    public function allPipelineEntityStates(): MorphMany
    {
        return $this->morphMany(PipelineEntityState::class, 'entity');
    }

    /**
     * Get the entity's state logs.
     */
    public function pipelineStateLogs(): MorphMany
    {
        return $this->morphMany(PipelineStateLog::class, 'entity')->orderBy('created_at', 'desc');
    }
}
