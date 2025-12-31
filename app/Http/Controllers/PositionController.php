<?php

namespace App\Http\Controllers;

use App\Exports\PositionExport;
use App\Http\Requests\ExportPositionRequest;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Http\Resources\PositionCollection;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    use CrudHelper;

    /**
     * Get the allowed sort fields for positions
     */
    protected function getAllowedSorts(): array
    {
        return ['id', 'name', 'created_at', 'updated_at'];
    }

    /**
     * Get the search fields for positions
     */
    protected function getSearchFields(): array
    {
        return ['name'];
    }

    /**
     * Display a listing of the positions.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Position::query();

        $this->applySearch($query, $request, $this->getSearchFields());
        $this->applySorting($query, $request, $this->getAllowedSorts());

        $positions = $query->paginate($perPage, ['*'], 'page', $page);

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
        $position = Position::create($request->validated());

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
        $validated = $request->validated();

        $filters = [
            'name' => $validated['name'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        // Remove null values
        $filters = array_filter($filters);

        // Generate filename with timestamp
        $filename = 'positions_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new \App\Exports\PositionExport($filters);
        \Maatwebsite\Excel\Facades\Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = \Illuminate\Support\Facades\Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
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
