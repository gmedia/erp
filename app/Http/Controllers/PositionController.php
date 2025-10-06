<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Http\Requests\ExportPositionRequest;
use App\Http\Resources\PositionResource;
use App\Http\Resources\PositionCollection;
use App\Exports\PositionExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class PositionController extends Controller
{
    /**
     * Display a listing of the positions with optional search and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        // Validate sorting parameters
        $allowedSorts = ['id', 'name', 'created_at', 'updated_at'];
        $request->validate([
            'sort_by' => ['sometimes', 'in:' . implode(',', $allowedSorts)],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
        ]);

        $query = Position::query();

        // Search by name
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply sorting
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
            ->setStatusCode(Response::HTTP_CREATED);
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

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Export positions to Excel based on optional filters.
     */
    public function export(ExportPositionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'name' => $validated['name'] ?? null,
            'sort_by' => $validated['sort_by'] ?? null,
            'sort_direction' => $validated['sort_direction'] ?? null,
        ];

        // Remove null values
        $filters = array_filter($filters);

        // Generate filename
        $filename = 'positions_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        // Create export and store on public disk
        $export = new PositionExport($filters);
        Excel::store($export, $filePath, 'public');

        // Generate public URL
        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
