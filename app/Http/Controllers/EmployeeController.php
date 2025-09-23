<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees with filtering and sorting.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        // Start building the query
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
        
        // Department filter - exact match
        if ($request->filled('department')) {
            $query->where('department', $request->get('department'));
        }
        
        // Position filter - exact match
        if ($request->filled('position')) {
            $query->where('position', $request->get('position'));
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
        $sortableColumns = ['name', 'email', 'department', 'position', 'salary', 'hire_date', 'created_at'];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = strtolower($request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        
        if (in_array($sortBy, $sortableColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        // Execute paginated query
        $employees = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'last_page' => $employees->lastPage(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
                'has_more_pages' => $employees->hasMorePages(),
            ]
        ]);
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:employees',
            'phone' => 'nullable|string|max:20',
            'department' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
        ]);

        $employee = Employee::create($validated);

        return response()->json($employee, Response::HTTP_CREATED);
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee)
    {
        return response()->json($employee);
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'department' => 'sometimes|string|max:255',
            'position' => 'sometimes|string|max:255',
            'salary' => 'sometimes|numeric|min:0',
            'hire_date' => 'sometimes|date',
        ]);

        $employee->update($validated);

        return response()->json($employee);
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}