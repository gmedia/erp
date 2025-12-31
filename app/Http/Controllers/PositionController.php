<?php

namespace App\Http\Controllers;


use App\Actions\ExportPositionsAction;
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
     * @queryParam search string Search positions by name. Example: manager
     * @queryParam sort_by string Sort by field. Enum: id,name,created_at,updated_at Example: created_at
     * @queryParam sort_direction string Sort direction. Enum: asc,desc Example: desc
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam page int Page number. Example: 1
     *
     * @param \App\Http\Requests\IndexPositionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IndexPositionRequest $request): JsonResponse
    {
        $query = Position::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->get('search')}%");
        }

        $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_direction', 'desc'));

        $positions = $query->paginate($request->get('per_page', 15));

        return (new PositionCollection($positions))->response();
    }

    /**
     * Store a newly created position in storage.
     *
     * @bodyParam name string required The name of the position. Example: Manager
     *
     * @param \App\Http\Requests\StorePositionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePositionRequest $request): JsonResponse
    {
        $position = Position::create($request->validated());

        return (new PositionResource($position))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Export positions to Excel based on filters.
     *
     * @bodyParam search string Search positions by name. Example: manager
     * @bodyParam sort_by string Sort by field. Enum: id,name,created_at,updated_at Example: created_at
     * @bodyParam sort_direction string Sort direction. Enum: asc,desc Example: desc
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
        $position->update($request->validated());

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
