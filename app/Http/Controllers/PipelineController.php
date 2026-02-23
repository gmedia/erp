<?php

namespace App\Http\Controllers;

use App\Actions\Pipelines\ExportPipelinesAction;
use App\Actions\Pipelines\IndexPipelinesAction;
use App\Domain\Pipelines\PipelineFilterService;
use App\DTOs\Pipelines\UpdatePipelineData;
use App\Http\Requests\Pipelines\ExportPipelineRequest;
use App\Http\Requests\Pipelines\IndexPipelineRequest;
use App\Http\Requests\Pipelines\StorePipelineRequest;
use App\Http\Requests\Pipelines\UpdatePipelineRequest;
use App\Http\Resources\Pipelines\PipelineCollection;
use App\Http\Resources\Pipelines\PipelineResource;
use App\Models\Pipeline;
use Illuminate\Http\JsonResponse;

class PipelineController extends Controller
{
    public function index(IndexPipelineRequest $request): JsonResponse
    {
        $pipelines = (new IndexPipelinesAction(app(PipelineFilterService::class)))->execute($request);
        return (new PipelineCollection($pipelines))->response();
    }

    public function store(StorePipelineRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->has('conditions') && $data['conditions']) {
            $data['conditions'] = json_decode($data['conditions'], true);
        }
        $data['created_by'] = auth()->id();
        
        $pipeline = Pipeline::create($data);
        return (new PipelineResource($pipeline))->response()->setStatusCode(201);
    }

    public function show(Pipeline $pipeline): JsonResponse
    {
        $pipeline->load(['creator']);
        return (new PipelineResource($pipeline))->response();
    }

    public function update(UpdatePipelineRequest $request, Pipeline $pipeline): JsonResponse
    {
        $dto = UpdatePipelineData::fromArray($request->validated());
        $pipeline->update($dto->toArray());
        
        return (new PipelineResource($pipeline->fresh(['creator'])))->response();
    }

    public function export(ExportPipelineRequest $request, ExportPipelinesAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    public function destroy(Pipeline $pipeline): JsonResponse
    {
        $pipeline->delete();
        return response()->json(null, 204);
    }
}
