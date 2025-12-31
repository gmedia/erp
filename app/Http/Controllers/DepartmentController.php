<?php

namespace App\Http\Controllers;

use App\Actions\ExportDepartmentsAction;
use App\Domain\DepartmentFilterService;
use App\Http\Requests\ExportDepartmentRequest;
use App\Http\Requests\IndexDepartmentRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentCollection;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the departments.
     *
     * @queryParam search string Search departments by name. Example: marketing
     * @queryParam sort_by string Sort by field. Enum: id,name,created_at,updated_at Example: created_at
     * @queryParam sort_direction string Sort direction. Enum: asc,desc Example: desc
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam page int Page number. Example: 1
     */
    public function index(IndexDepartmentRequest $request): JsonResponse
    {
        $query = Department::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->get('search')}%");
        }

        $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_direction', 'desc'));

        $departments = $query->paginate($request->get('per_page', 15));

        return (new DepartmentCollection($departments))->response();
    }

    /**
     * Store a newly created department in storage.
     *
     * @bodyParam name string required The name of the department. Example: Marketing
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());

        return (new DepartmentResource($department))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department): JsonResponse
    {
        return (new DepartmentResource($department))->response();
    }

    /**
     * Update the specified department in storage.
     */
    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());

        return (new DepartmentResource($department))->response();
    }

    /**
     * Remove the specified department from storage.
     */
    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return response()->json(null, 204);
    }

    /**
     * Export departments to Excel based on filters.
     *
     * @bodyParam search string Search departments by name. Example: marketing
     * @bodyParam sort_by string Sort by field. Enum: id,name,created_at,updated_at Example: created_at
     * @bodyParam sort_direction string Sort direction. Enum: asc,desc Example: desc
     */
    public function export(ExportDepartmentRequest $request): JsonResponse
    {
        return (new ExportDepartmentsAction(app(DepartmentFilterService::class)))->execute($request);
    }
}
