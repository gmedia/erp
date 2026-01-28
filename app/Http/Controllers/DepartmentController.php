<?php

namespace App\Http\Controllers;

use App\Actions\Departments\ExportDepartmentsAction;
use App\Actions\Departments\IndexDepartmentsAction;
use App\Http\Requests\Departments\ExportDepartmentRequest;
use App\Http\Requests\Departments\IndexDepartmentRequest;
use App\Http\Requests\Departments\StoreDepartmentRequest;
use App\Http\Requests\Departments\UpdateDepartmentRequest;
use App\Http\Resources\Departments\DepartmentCollection;
use App\Http\Resources\Departments\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

/**
 * Controller for department management operations.
 *
 * Handles CRUD operations and export functionality for departments.
 */
class DepartmentController extends Controller
{
    /**
     * Display a listing of the departments.
     *
     * Supports pagination, search filtering, and sorting.
     */
    public function index(IndexDepartmentRequest $request): JsonResponse
    {
        $departments = (new IndexDepartmentsAction())->execute($request);

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
     */
    public function export(ExportDepartmentRequest $request): JsonResponse
    {
        return (new ExportDepartmentsAction())->execute($request);
    }
}
