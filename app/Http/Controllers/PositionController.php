<?php

namespace App\Http\Controllers;


use App\Actions\CreatePositionAction;
use App\Actions\ExportPositionsAction;
use App\Actions\IndexPositionsAction;
use App\Actions\UpdatePositionAction;
use App\Domain\PositionFilterService;
use App\Http\Requests\ExportPositionRequest;
use App\Http\Requests\IndexPositionRequest;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Http\Resources\PositionCollection;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use Illuminate\Http\JsonResponse;

class PositionController extends Controller
{

    /**
     * Display a listing of the positions.
     *
     * @param \App\Http\Requests\IndexPositionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IndexPositionRequest $request): JsonResponse
    {
        $positions = (new IndexPositionsAction(app(PositionFilterService::class)))->execute($request);

        return (new PositionCollection($positions))->response();
    }

    /**
     * Store a newly created position in storage.
     *
     * @param \App\Http\Requests\StorePositionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePositionRequest $request): JsonResponse
    {
        $position = (new CreatePositionAction())->execute($request->validated());

        return (new PositionResource($position))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Export positions to Excel based on filters.
     *
     * @param \App\Http\Requests\ExportPositionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(ExportPositionRequest $request): JsonResponse
    {
        return (new ExportPositionsAction())->execute($request);
    }

    /**
     * Display the specified position.
     *
     * @param \App\Models\Position $position
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Position $position): JsonResponse
    {
        return (new PositionResource($position))->response();
    }

    /**
     * Update the specified position in storage.
     *
     * @param \App\Http\Requests\UpdatePositionRequest $request
     * @param \App\Models\Position $position
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $position = (new UpdatePositionAction())->execute($position, $request->validated());

        return (new PositionResource($position))->response();
    }

    /**
     * Remove the specified position from storage.
     *
     * @param \App\Models\Position $position
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Position $position): JsonResponse
    {
        $position->delete();
        return response()->json(null, 204);
    }
}
