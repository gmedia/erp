<?php

namespace App\Http\Controllers;

use App\Actions\Employees\ExportEmployeesAction;
use App\Actions\Employees\IndexEmployeesAction;
use App\Actions\Employees\SyncEmployeePermissionsAction;
use App\Domain\Employees\EmployeeFilterService;
use App\Http\Requests\Employees\ExportEmployeeRequest;
use App\Http\Requests\Employees\ImportEmployeeRequest;
use App\Http\Requests\Employees\IndexEmployeeRequest;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\SyncPermissionsRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Http\Resources\Employees\EmployeeCollection;
use App\Http\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;

/**
 * Controller for employee management operations.
 *
 * Handles CRUD operations, export functionality, and permission management for employees.
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
     * Import employees from Excel/CSV.
     *
     * @param  \App\Http\Requests\Employees\ImportEmployeeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(ImportEmployeeRequest $request): JsonResponse
    {
        $summary = (new \App\Actions\Employees\ImportEmployeesAction)->execute($request->file('file'));

        return response()->json($summary);
    }

    /**
     * Get permissions for the specified employee.
     *
     * Returns an array of permission IDs assigned to the employee.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\JsonResponse
     */
    public function permissions(Employee $employee): JsonResponse
    {
        return response()->json($employee->permissions()->pluck('permissions.id'));
    }

    /**
     * Sync permissions for the specified employee.
     *
     * Replaces all current permissions with the provided permission IDs.
     *
     * @param  \App\Http\Requests\Employees\SyncPermissionsRequest  $request
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncPermissions(SyncPermissionsRequest $request, Employee $employee): JsonResponse
    {
        (new SyncEmployeePermissionsAction())->execute($employee, $request->validated('permissions', []));

        return response()->json(['message' => 'Permissions updated successfully.']);
    }

}
