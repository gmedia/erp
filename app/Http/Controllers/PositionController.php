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
    /**
     * Get the model class for this controller
     */
    protected function getModelClass(): string
    {
        return Position::class;
    }

    /**
     * Get the resource class for this controller
     */
    protected function getResourceClass(): string
    {
        return PositionResource::class;
    }

    /**
     * Get the collection class for this controller
     */
    protected function getCollectionClass(): string
    {
        return PositionCollection::class;
    }

    /**
     * Get the export class for this controller
     */
    protected function getExportClass(): string
    {
        return PositionExport::class;
    }

    /**
     * Get the export request class for this controller
     */
    protected function getExportRequestClass(): string
    {
        return ExportPositionRequest::class;
    }

    /**
     * Display a listing of the positions.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $query = Position::query();

        // Apply search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply sorting
        $allowedSorts = ['id', 'name', 'created_at', 'updated_at'];
        $request->validate([
            'sort_by' => ['sometimes', 'in:' . implode(',', $allowedSorts)],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
        ]);

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $positions = $query->paginate($perPage, ['*'], 'page', $page);

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
     * Export positions to Excel based on filters.
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
     */
    public function show(Position $position)
    {
        return (new PositionResource($position))->response();
    }

    /**
     * Update the specified position in storage.
     */
    public function update(UpdatePositionRequest $request, Position $position)
    {
        $position->update($request->validated());
        return (new PositionResource($position))->response();
    }

    /**
     * Remove the specified position from storage.
     */
    public function destroy(Position $position)
    {
        $position->delete();
        return response()->json(null, 204);
    }
}
