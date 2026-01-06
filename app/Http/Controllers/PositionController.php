<?php

namespace App\Http\Controllers;

use App\Actions\Positions\ExportPositionsAction;
use App\Domain\Positions\PositionFilterService;
use App\Http\Requests\Positions\ExportPositionRequest;
use App\Http\Requests\Positions\IndexPositionRequest;
use App\Http\Requests\Positions\StorePositionRequest;
use App\Http\Requests\Positions\UpdatePositionRequest;
use App\Http\Resources\Positions\PositionCollection;
use App\Http\Resources\Positions\PositionResource;
use App\Models\Position;
use Illuminate\Http\JsonResponse;

class PositionController extends Controller
{
    /**
     * Display a listing of the positions.
     *
     * @queryParam search string Search positions by name. Example: manager
     * @queryParam sort_by string Sort by field. Enum: id,name,created_at,updated_at Example: created_at
     * @queryParam sort_direction string Sort direction. Enum: asc,desc Example: desc
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam page int Page number. Example: 1
     */
    public function index(IndexPositionRequest $request, PositionFilterService $filterService): JsonResponse
    {
        $query = Position::query();

        if ($request->filled('search')) {
            $filterService->applySearch($query, $request->get('search'), ['name']);
        }

        $filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'name', 'created_at', 'updated_at']
        );

        $positions = $query->paginate($request->get('per_page', 15));

        return (new PositionCollection($positions))->response();
    }

    /**
     * Store a newly created position in storage.
     *
     * @bodyParam name string required The name of the position. Example: Manager
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
     *
     * @bodyParam search string Search positions by name. Example: manager
     * @bodyParam sort_by string Sort by field. Enum: id,name,created_at,updated_at Example: created_at
     * @bodyParam sort_direction string Sort direction. Enum: asc,desc Example: desc
     */
    public function export(ExportPositionRequest $request): JsonResponse
    {
        return (new ExportPositionsAction)->execute($request);
    }
}
