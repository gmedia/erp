<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Requests\ExportEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeCollection;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees with filtering and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        // Start building the query
        // Validate sorting parameters
        $allowedSorts = ['id', 'name', 'email', 'phone', 'department', 'position', 'salary', 'hire_date', 'created_at', 'updated_at'];
        $request->validate([
            'sort_by' => ['sometimes', 'in:' . implode(',', $allowedSorts)],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
        ]);
        $query = Employee::query();

        // Search functionality - search across name, email, phone, department, position
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Apply department and position filters only when a search term is not provided.
        if (!$request->filled('search')) {
            // Department filter - exact match
            if ($request->filled('department')) {
                $query->where('department', 'like', $request->get('department'));
            }

            // Position filter - exact match (updated to allow partial matches for robustness)
            if ($request->filled('position')) {
                $query->where('position', 'like', $request->get('position'));
            }
        }

        // Salary range filtering
        if ($request->filled('salary_min')) {
            $query->where('salary', '>=', $request->get('salary_min'));
        }

        if ($request->filled('salary_max')) {
            $query->where('salary', '<=', $request->get('salary_max'));
        }

        // Hire date range filtering
        if ($request->filled('hire_date_from')) {
            $query->whereDate('hire_date', '>=', $request->get('hire_date_from'));
        }

        if ($request->filled('hire_date_to')) {
            $query->whereDate('hire_date', '<=', $request->get('hire_date_to'));
        }

        // Server-side sorting
        $sortableColumns = [
            'id',
            'name',
            'email',
            'phone',
            'department',
            'position',
            'salary',
            'hire_date',
            'created_at',
            'updated_at',
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        // Accept both `sort_dir` and legacy `sort_order` parameters
        $sortDir = $request->get('sort_direction', 'desc');
        $sortOrder = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, $sortableColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Execute paginated query
        $employees = $query->paginate($perPage, ['*'], 'page', $page);

        return (new EmployeeCollection($employees))->response();
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());

        return (new EmployeeResource($employee))->response()->setStatusCode(Response::HTTP_CREATED);
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
