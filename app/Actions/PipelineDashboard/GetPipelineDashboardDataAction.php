<?php

namespace App\Actions\PipelineDashboard;

use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetPipelineDashboardDataAction
{
    public function execute(array $filters): array
    {
        $pipelineId = $filters['pipeline_id'] ?? null;
        $entityType = $filters['entity_type'] ?? null;
        $staleDays = (int) ($filters['stale_days'] ?? 7);

        // Fetch Pipeline to get States structure
        $pipelineQuery = Pipeline::with(['states' => function ($query) {
            $query->orderBy('sort_order');
        }])->where('is_active', true);

        if ($pipelineId) {
            $pipelineQuery->where('id', $pipelineId);
        }
        
        $pipelines = $pipelineQuery->get();
        if ($pipelines->isEmpty()) {
            return [
                'summary' => [],
                'stale_entities' => [],
            ];
        }

        $summaryData = [];
        // Map all valid states for selected pipeline(s)
        foreach ($pipelines as $pipeline) {
            foreach ($pipeline->states as $state) {
                $summaryData[$state->id] = [
                    'state_id' => $state->id,
                    'name' => $state->name,
                    'code' => $state->code,
                    'color' => $state->color ?? '#6B7280',
                    'count' => 0,
                ];
            }
        }

        // Query Entity States
        $query = PipelineEntityState::query()
            ->select('current_state_id', DB::raw('count(*) as count'));

        if ($pipelineId) {
            $query->where('pipeline_id', $pipelineId);
        } else {
            $query->whereIn('pipeline_id', $pipelines->pluck('id'));
        }

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }

        $aggregatedStateCounts = $query->groupBy('current_state_id')->get();

        foreach ($aggregatedStateCounts as $row) {
            if (isset($summaryData[$row->current_state_id])) {
                $summaryData[$row->current_state_id]['count'] = $row->count;
            }
        }

        // Fetch Stale Entities (in intermediate state for more than X days)
        $staleThreshold = Carbon::now()->subDays($staleDays);
        
        $staleQuery = PipelineEntityState::with(['currentState', 'lastTransitionedBy'])
            ->whereHas('currentState', function ($q) {
                // Focus stale detection on intermediate states where things can get stuck
                $q->where('type', 'intermediate');
            })
            ->where('last_transitioned_at', '<', $staleThreshold);

        if ($pipelineId) {
            $staleQuery->where('pipeline_id', $pipelineId);
        } else {
            $staleQuery->whereIn('pipeline_id', $pipelines->pluck('id'));
        }
        
        if ($entityType) {
            $staleQuery->where('entity_type', $entityType);
        }

        $staleEntitiesModels = $staleQuery->orderBy('last_transitioned_at', 'asc')
            ->limit(50)
            ->get();
            
        // Map polymorphic entity names for display (e.g. Asset code/name)
        $staleEntities = $staleEntitiesModels->map(function ($state) {
            $entityName = "ID: {$state->entity_id}"; // fallback
            
            // Try to resolve entity and get a descriptive string
            $modelClass = $state->entity_type;
            if (class_exists($modelClass)) {
                $entity = app($modelClass)->find($state->entity_id);
                if ($entity) {
                    $entityName = $entity->name ?? $entity->code ?? $entity->title ?? $entity->reference ?? $entityName;
                }
            }
            
            $shortType = class_basename($state->entity_type);
            
            return [
                'id' => $state->id,
                'entity_type' => $shortType,
                'entity_name' => $entityName,
                'entity_id' => $state->entity_id,
                'current_state' => [
                    'name' => $state->currentState->name,
                    'color' => $state->currentState->color,
                ],
                'days_in_state' => Carbon::parse($state->last_transitioned_at)->diffInDays(Carbon::now()),
                'last_transitioned_at' => $state->last_transitioned_at->toISOString(),
                'last_transitioned_by' => $state->lastTransitionedBy?->name ?? 'System',
            ];
        });

        return [
            'summary' => array_values($summaryData),
            'stale_entities' => $staleEntities,
        ];
    }
}
