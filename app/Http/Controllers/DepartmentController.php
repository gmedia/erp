<?php

namespace App\Http\Controllers;

use App\Actions\Departments\ExportDepartmentsAction;
use App\Actions\Departments\IndexDepartmentsAction;
use App\Domain\Departments\DepartmentFilterService;
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
     *
     * @param  \App\Http\Requests\Departments\IndexDepartmentRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IndexDepartmentRequest $request): JsonResponse
    {
        $departments = (new IndexDepartmentsAction(app(DepartmentFilterService::class)))->execute($request);

        return (new DepartmentCollection($departments))->response();
    }

    /**
     * Store a newly created department in storage.
     *
     * @param  \App\Http\Requests\Departments\StoreDepartmentRequest  $request
     * @return \Illuminate\Http\JsonResponse
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
     *
     * @param  \App\Models\Department  $department
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Department $department): JsonResponse
    {
        return (new DepartmentResource($department))->response();
    }

    /**
     * Update the specified department in storage.
     *
     * @param  \App\Http\Requests\Departments\UpdateDepartmentRequest  $request
     * @param  \App\Models\Department  $department
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());

        return (new DepartmentResource($department))->response();
    }

    /**
     * Remove the specified department from storage.
     *
     * @param  \App\Models\Department  $department
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return response()->json(null, 204);
    }

    /**
     * Export departments to Excel based on filters.
     *
     * @param  \App\Http\Requests\Departments\ExportDepartmentRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(ExportDepartmentRequest $request): JsonResponse
    {
        return (new ExportDepartmentsAction(app(DepartmentFilterService::class)))->execute($request);
    }
}
