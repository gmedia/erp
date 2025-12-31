<?php

namespace App\Http\Controllers;


use App\Actions\CreateDepartmentAction;
use App\Actions\ExportDepartmentsAction;
use App\Actions\IndexDepartmentsAction;
use App\Actions\UpdateDepartmentAction;
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
     * @param \App\Http\Requests\IndexDepartmentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IndexDepartmentRequest $request): JsonResponse
    {
        $departments = (new IndexDepartmentsAction())->execute($request);

        return (new DepartmentCollection($departments))->response();
    }

    /**
     * Store a newly created department in storage.
     *
     * @param \App\Http\Requests\StoreDepartmentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = (new CreateDepartmentAction())->execute($request->validated());

        return (new DepartmentResource($department))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Export departments to Excel based on filters.
     *
     * @param \App\Http\Requests\ExportDepartmentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(ExportDepartmentRequest $request): JsonResponse
    {
        return (new ExportDepartmentsAction())->execute($request);
    }

    /**
     * Display the specified department.
     *
     * @param \App\Models\Department $department
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Department $department): JsonResponse
    {
        return (new DepartmentResource($department))->response();
    }

    /**
     * Update the specified department in storage.
     *
     * @param \App\Http\Requests\UpdateDepartmentRequest $request
     * @param \App\Models\Department $department
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department = (new UpdateDepartmentAction())->execute($department, $request->validated());

        return (new DepartmentResource($department))->response();
    }

    /**
     * Remove the specified department from storage.
     *
     * @param \App\Models\Department $department
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Department $department): JsonResponse
    {
        $department->delete();
        return response()->json(null, 204);
    }
}
