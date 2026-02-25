<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pipelines\StorePipelineTransitionRequest;
use App\Http\Requests\Pipelines\UpdatePipelineTransitionRequest;
use App\Http\Resources\Pipelines\PipelineTransitionResource;
use App\Models\Pipeline;
use App\Models\PipelineTransition;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PipelineTransitionController extends Controller
{
    public function index(Pipeline $pipeline): JsonResponse
    {
        $transitions = $pipeline->transitions()
            ->with(['fromState', 'toState', 'actions'])
            ->orderBy('sort_order')
            ->get();
            
        return PipelineTransitionResource::collection($transitions)->response();
    }

    public function store(StorePipelineTransitionRequest $request, Pipeline $pipeline): JsonResponse
    {
        $validated = $request->validated();
        
        $transition = DB::transaction(function () use ($validated, $pipeline) {
            $transitionData = collect($validated)->except('actions')->toArray();
            $transition = $pipeline->transitions()->create($transitionData);
            
            if (isset($validated['actions']) && is_array($validated['actions'])) {
                foreach ($validated['actions'] as $actionData) {
                    $transition->actions()->create($actionData);
                }
            }
            
            return $transition->load(['fromState', 'toState', 'actions']);
        });

        return (new PipelineTransitionResource($transition))->response()->setStatusCode(201);
    }

    public function update(UpdatePipelineTransitionRequest $request, Pipeline $pipeline, PipelineTransition $transition): JsonResponse
    {
        $validated = $request->validated();
        
        $transition = DB::transaction(function () use ($validated, $transition, $request) {
            $transitionData = collect($validated)->except('actions')->toArray();
            $transition->update($transitionData);
            
            if (isset($validated['actions']) && is_array($validated['actions'])) {
                // Sync actions: delete ones not in the request, update existing, create new
                $providedIds = collect($validated['actions'])->pluck('id')->filter()->toArray();
                
                // Delete removed actions
                $transition->actions()->whereNotIn('id', $providedIds)->delete();
                
                // Update or create
                foreach ($validated['actions'] as $actionData) {
                    if (isset($actionData['id'])) {
                        $transition->actions()->where('id', $actionData['id'])->update(collect($actionData)->except('id')->toArray());
                    } else {
                        $transition->actions()->create($actionData);
                    }
                }
            } else {
                // If actions array is explicitly passed as empty, delete all.
                // If not passed at all, we could also delete all (or keep them based on design).
                // Usually for nested arrays in put/patch, omitted means "don't touch", but empty array means "delete all".
                if ($request->has('actions')) {
                    $transition->actions()->delete();
                }
            }
            
            return $transition->fresh(['fromState', 'toState', 'actions']);
        });

        return (new PipelineTransitionResource($transition))->response();
    }

    public function destroy(Pipeline $pipeline, PipelineTransition $transition): JsonResponse
    {
        $transition->delete();
        return response()->json(null, 204);
    }
}
