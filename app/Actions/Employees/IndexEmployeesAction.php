<?php

namespace App\Actions\Employees;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\Employees\EmployeeFilterService;
use App\Http\Requests\Employees\IndexEmployeeRequest;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexEmployeesAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private EmployeeFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated employees with filters.
     */
    public function execute(IndexEmployeeRequest $request): LengthAwarePaginator
    {
        $query = Employee::query()->with(['currentEmployment.department', 'currentEmployment.position', 'currentEmployment.branch']);

        return $this->handleSearchOrPrimaryIndexRequest(
            $request,
            $query,
            $this->filterService,
            [
                'name',
                'email',
                'phone',
                'employee_id',
            ],
            ['department_id', 'position_id', 'branch_id', 'employment_status'],
            ['salary_min', 'salary_max', 'hire_date_from', 'hire_date_to'],
            'employees.created_at',
            [
                'id',
                'employee_id',
                'name',
                'email',
                'phone',
                'employments.department_id',
                'employments.position_id',
                'employments.branch_id',
                'employments.salary',
                'employments.employment_status',
                'employments.hire_date',
                'employees.created_at',
                'updated_at',
            ],
        );
    }
}
