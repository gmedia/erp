<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportEmployeeRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeCollection;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmployeeController extends Controller
{
    use CrudHelper;

    /**
     * Get the allowed sort fields for employees
     */
    protected function getAllowedSorts(): array
    {
        return ['id', 'name', 'email', 'phone', 'department', 'position', 'salary', 'hire_date', 'created_at', 'updated_at'];
    }

    /**
     * Get the search fields for employees
     */
    protected function getSearchFields(): array
    {
        return ['name', 'email', 'phone', 'department', 'position'];
    }

    /**
     * Display a listing of the employees with filtering and sorting.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Employee::query();

        // Search functionality - search across name, email, phone, department, position
        if ($request->filled('search')) {
            $this->applySearch($query, $request, $this->getSearchFields());
        } else {
            // Apply department and position filters only when no search term is provided
            $this->applyAdvancedFilters($query, $request, true);
        }

        // Apply salary and hire date filters (always applied)
        $this->applyAdvancedFilters($query, $request, false);

        $this->applySorting($query, $request, $this->getAllowedSorts());

        // Execute paginated query
        $employees = $query->paginate($perPage, ['*'], 'page', $page);

        return (new EmployeeCollection($employees))->response();
    }

    /**
     * Store a newly created employee in storage.
     *
     * @param \App\Http\Requests\StoreEmployeeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());

        return (new EmployeeResource($employee))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified employee.
     *
     * @param \App\Models\Employee $employee
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Employee $employee): JsonResponse
    {
        return (new EmployeeResource($employee))->response();
    }

    /**
     * Update the specified employee in storage.
     *
     * @param \App\Http\Requests\UpdateEmployeeRequest $request
     * @param \App\Models\Employee $employee
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
     * @param \App\Models\Employee $employee
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Export employees to Excel based on filters.
     *
     * @param \App\Http\Requests\ExportEmployeeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(ExportEmployeeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Map request parameters to match EmployeeExport expectations
        $filters = [
            'search' => $validated['search'] ?? null,
            'department' => $validated['department'] ?? null,
            'position' => $validated['position'] ?? null,
            'min_salary' => $validated['min_salary'] ?? null,
            'max_salary' => $validated['max_salary'] ?? null,
            'hire_date_from' => $validated['hire_date_from'] ?? null,
            'hire_date_to' => $validated['hire_date_to'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        // Remove null values
        $filters = array_filter($filters);

        // Generate filename with timestamp
        $filename = 'employees_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new \App\Exports\EmployeeExport($filters);
        \Maatwebsite\Excel\Facades\Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = \Illuminate\Support\Facades\Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
