<?php

namespace App\Http\Controllers;

use App\Actions\CreateEmployeeAction;
use App\Actions\ExportEmployeesAction;
use App\Actions\IndexEmployeesAction;
use App\Actions\UpdateEmployeeAction;
use App\Domain\EmployeeFilterService;
use App\DTOs\StoreEmployeeData;
use App\DTOs\UpdateEmployeeData;
use App\Http\Requests\ExportEmployeeRequest;
use App\Http\Requests\IndexEmployeeRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeCollection;
use App\Http\Resources\EmployeeResource;
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
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $data = StoreEmployeeData::fromArray($request->validated());
        $employee = (new CreateEmployeeAction)->execute($data);

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
        $data = UpdateEmployeeData::fromArray($request->validated());
        $employee = (new UpdateEmployeeAction)->execute($employee, $data);

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
