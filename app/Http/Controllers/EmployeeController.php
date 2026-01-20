<?php

namespace App\Http\Controllers;

use App\Actions\Employees\ExportEmployeesAction;
use App\Actions\Employees\IndexEmployeesAction;
use App\Domain\Employees\EmployeeFilterService;
use App\Http\Requests\Employees\ExportEmployeeRequest;
use App\Http\Requests\Employees\IndexEmployeeRequest;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Http\Resources\Employees\EmployeeCollection;
use App\Http\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for employee management operations.
 *
 * Handles CRUD operations and export functionality for employees.
 */
class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees with filtering and sorting.
     *
     * Supports pagination, search, advanced filters (department, position, salary, hire date), and sorting.
     *
     * @param  \App\Http\Requests\Employees\IndexEmployeeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IndexEmployeeRequest $request): JsonResponse
    {
        $employees = (new IndexEmployeesAction(app(EmployeeFilterService::class)))->execute($request);

        return (new EmployeeCollection($employees))->response();
    }

    /**
     * Store a newly created employee in storage.
     *
     * @param  \App\Http\Requests\Employees\StoreEmployeeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());

        return (new EmployeeResource($employee))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified employee.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Employee $employee): JsonResponse
    {
        return (new EmployeeResource($employee))->response();
    }

    /**
     * Update the specified employee in storage.
     *
     * @param  \App\Http\Requests\Employees\UpdateEmployeeRequest  $request
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee->update($request->validated());

        return (new EmployeeResource($employee))->response();
    }

    /**
     * Remove the specified employee from storage.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return response()->json(null, 204);
    }

    /**
     * Export employees to Excel based on filters.
     *
     * @param  \App\Http\Requests\Employees\ExportEmployeeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(ExportEmployeeRequest $request): JsonResponse
    {
        return (new ExportEmployeesAction)->execute($request);
    }

    /**
     * Get permissions for the specified employee.
     */
    public function permissions(Employee $employee): JsonResponse
    {
        return response()->json($employee->permissions()->pluck('id'));
    }

    /**
     * Sync permissions for the specified employee.
     */
    public function syncPermissions(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $employee->permissions()->sync($request->input('permissions', []));

        return response()->json(['message' => 'Permissions updated successfully.']);
    }

}
