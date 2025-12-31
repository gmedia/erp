<?php

namespace App\Http\Controllers;

use App\Actions\ExportDepartmentsAction;
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
    use CrudHelper;

    /**
     * Get the allowed sort fields for departments
     */
    protected function getAllowedSorts(): array
    {
        return ['id', 'name', 'created_at', 'updated_at'];
    }

    /**
     * Get the search fields for departments
     */
    protected function getSearchFields(): array
    {
        return ['name'];
    }

    /**
     * Display a listing of the departments.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Department::query();

        $this->applySearch($query, $request, $this->getSearchFields());
        $this->applySorting($query, $request, $this->getAllowedSorts());

        $departments = $query->paginate($perPage, ['*'], 'page', $page);

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
        $department = Department::create($request->validated());

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
        $department->update($request->validated());
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
