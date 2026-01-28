<?php

namespace App\Http\Controllers;

use App\Actions\Positions\ExportPositionsAction;
use App\Actions\Positions\IndexPositionsAction;
use App\Http\Requests\Positions\ExportPositionRequest;
use App\Http\Requests\Positions\IndexPositionRequest;
use App\Http\Requests\Positions\StorePositionRequest;
use App\Http\Requests\Positions\UpdatePositionRequest;
use App\Http\Resources\Positions\PositionCollection;
use App\Http\Resources\Positions\PositionResource;
use App\Models\Position;
use Illuminate\Http\JsonResponse;

/**
 * Controller for position management operations.
 *
 * Handles CRUD operations and export functionality for positions.
 */
class PositionController extends Controller
{
    /**
     * Display a listing of the positions.
     *
     * Supports pagination, search filtering, and sorting.
     */
    public function index(IndexPositionRequest $request): JsonResponse
    {
        $positions = (new IndexPositionsAction())->execute($request);

        return (new PositionCollection($positions))->response();
    }

    /**
     * Store a newly created position in storage.
     */
    public function store(StorePositionRequest $request): JsonResponse
    {
        $position = Position::create($request->validated());

        return (new PositionResource($position))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified position.
     */
    public function show(Position $position): JsonResponse
    {
        return (new PositionResource($position))->response();
    }

    /**
     * Update the specified position in storage.
     */
    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $position->update($request->validated());

        return (new PositionResource($position))->response();
    }

    /**
     * Remove the specified position from storage.
     */
    public function destroy(Position $position): JsonResponse
    {
        $position->delete();

        return response()->json(null, 204);
    }

    /**
     * Export positions to Excel based on filters.
     */
    public function export(ExportPositionRequest $request): JsonResponse
    {
        return (new ExportPositionsAction())->execute($request);
    }
}
