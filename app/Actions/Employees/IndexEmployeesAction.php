<?php

namespace App\Actions\Employees;

use App\Domain\Employees\EmployeeFilterService;
use App\Http\Requests\Employees\IndexEmployeeRequest;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexEmployeesAction
{
    public function __construct(
        private EmployeeFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated employees with filters.
     */
    public function execute(IndexEmployeeRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Employee::query()->with(['department', 'position', 'branch']);

        // Search functionality - search across name, email, phone
        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name', 'email', 'phone']);
        } else {
            $this->filterService->applyAdvancedFilters($query, [
                'department_id' => $request->get('department_id'),
                'position_id' => $request->get('position_id'),
                'branch_id' => $request->get('branch_id'),
            ]);
        }

        // Apply salary and hire date filters (always applied)
        $this->filterService->applyAdvancedFilters($query, [
            'salary_min' => $request->get('salary_min'),
            'salary_max' => $request->get('salary_max'),
            'hire_date_from' => $request->get('hire_date_from'),
            'hire_date_to' => $request->get('hire_date_to'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'name', 'email', 'phone', 'department_id', 'position_id', 'branch_id', 'salary', 'hire_date', 'created_at', 'updated_at']
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get pagination parameters from request
     */
    private function getPaginationParams($request): array
    {
        return [
            'perPage' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ];
    }
}
