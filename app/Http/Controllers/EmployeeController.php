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
     */
    public function index(IndexEmployeeRequest $request): JsonResponse
    {
        $employees = (new IndexEmployeesAction(app(EmployeeFilterService::class)))->execute($request);

        return (new EmployeeCollection($employees))->response();
    }

    /**
     * Store a newly created employee in storage.
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
     */
    public function show(Employee $employee): JsonResponse
    {
        $employee->load(['department', 'position', 'branch']);

        return (new EmployeeResource($employee))->response();
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee->update($request->validated());

        return (new EmployeeResource($employee))->response();
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee): JsonResponse
    {
        return $this->destroyModel($employee);
    }

    /**
     * Export employees to Excel based on filters.
     */
    public function export(ExportEmployeeRequest $request): JsonResponse
    {
        return (new ExportEmployeesAction)->execute($request);
    }

    /**
     * Import employees from Excel/CSV.
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
     */
    public function permissions(Employee $employee): JsonResponse
    {
        return response()->json($employee->permissions()->pluck('permissions.id'));
    }

    /**
     * Sync permissions for the specified employee.
     *
     * Replaces all current permissions with the provided permission IDs.
     */
    public function syncPermissions(SyncPermissionsRequest $request, Employee $employee): JsonResponse
    {
        (new SyncEmployeePermissionsAction)->execute($employee, $request->validated('permissions', []));

        return response()->json(['message' => 'Permissions updated successfully.']);
    }
}
