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
use Illuminate\Http\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees with filtering and sorting.
     */
    public function index(IndexEmployeeRequest $request): JsonResponse
    {
        $employees = (new IndexEmployeesAction(app(EmployeeFilterService::class)))->execute($request);

        return (new EmployeeCollection($employees))->response();
    }

    /**
     * Store a newly created employee in storage.
     *
     * @bodyParam name string required The employee's name. Example: John Doe
     * @bodyParam email string required The employee's email. Example: john.doe@example.com
     * @bodyParam phone string The employee's phone number. Example: 555-1234
     * @bodyParam department string The employee's department. Example: Engineering
     * @bodyParam position string The employee's position. Example: Developer
     * @bodyParam salary numeric The employee's salary. Example: 75000.00
     * @bodyParam hire_date date The employee's hire date. Example: 2023-01-15
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
        $employee->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Export employees to Excel based on filters.
     */
    public function export(ExportEmployeeRequest $request): JsonResponse
    {
        return (new ExportEmployeesAction)->execute($request);
    }
}
