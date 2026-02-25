<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pipelines\StorePipelineStateRequest;
use App\Http\Requests\Pipelines\UpdatePipelineStateRequest;
use App\Http\Resources\Pipelines\PipelineStateResource;
use App\Models\Pipeline;
use App\Models\PipelineState;
use Illuminate\Http\JsonResponse;

class PipelineStateController extends Controller
{
    public function index(Pipeline $pipeline): JsonResponse
    {
        $states = $pipeline->states;
        return PipelineStateResource::collection($states)->response();
    }

    public function store(StorePipelineStateRequest $request, Pipeline $pipeline): JsonResponse
    {
        $data = $request->validated();
        $state = $pipeline->states()->create($data);
        return (new PipelineStateResource($state))->response()->setStatusCode(201);
    }

    public function update(UpdatePipelineStateRequest $request, Pipeline $pipeline, PipelineState $state): JsonResponse
    {
        // Ensure state belongs to pipeline
        if ($state->pipeline_id !== $pipeline->id) {
            abort(404);
        }
        $data = $request->validated();
        $state->update($data);
        return (new PipelineStateResource($state))->response();
    }

    public function destroy(Pipeline $pipeline, PipelineState $state): JsonResponse
    {
        // Ensure state belongs to pipeline
        if ($state->pipeline_id !== $pipeline->id) {
            abort(404);
        }
        $state->delete();
        return response()->json(null, 204);
    }
}
