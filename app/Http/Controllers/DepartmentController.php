<?php

namespace App\Http\Controllers;

use App\Exports\DepartmentExport;
use App\Http\Requests\ExportDepartmentRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentCollection;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Get the model class for this controller
     */
    protected function getModelClass(): string
    {
        return Department::class;
    }

    /**
     * Get the resource class for this controller
     */
    protected function getResourceClass(): string
    {
        return DepartmentResource::class;
    }

    /**
     * Get the collection class for this controller
     */
    protected function getCollectionClass(): string
    {
        return DepartmentCollection::class;
    }

    /**
     * Get the export class for this controller
     */
    protected function getExportClass(): string
    {
        return DepartmentExport::class;
    }

    /**
     * Get the export request class for this controller
     */
    protected function getExportRequestClass(): string
    {
        return ExportDepartmentRequest::class;
    }

    /**
     * Display a listing of the departments.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $query = Department::query();

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

        $departments = $query->paginate($perPage, ['*'], 'page', $page);

        return (new DepartmentCollection($departments))->response();
    }

    /**
     * Store a newly created department in storage.
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());

        return (new DepartmentResource($department))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Export departments to Excel based on filters.
     */
    public function export(ExportDepartmentRequest $request): JsonResponse
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
        $filename = 'departments_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new \App\Exports\DepartmentExport($filters);
        \Maatwebsite\Excel\Facades\Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = \Illuminate\Support\Facades\Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department)
    {
        return (new DepartmentResource($department))->response();
    }

    /**
     * Update the specified department in storage.
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());
        return (new DepartmentResource($department))->response();
    }

    /**
     * Remove the specified department from storage.
     */
    public function destroy(Department $department)
    {
        $department->delete();
        return response()->json(null, 204);
    }
}
