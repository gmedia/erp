<?php

namespace App\Actions\Departments;

use App\Domain\Departments\DepartmentFilterService;
use App\Http\Requests\Departments\IndexDepartmentRequest;
use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Action to retrieve paginated departments with filtering and sorting.
 */
class IndexDepartmentsAction
{
    public function __construct(
        private DepartmentFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated departments with filters.
     *
     * @param  \App\Http\Requests\Departments\IndexDepartmentRequest  $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<\App\Models\Department>
     */
    public function execute(IndexDepartmentRequest $request): LengthAwarePaginator
    {
        $query = Department::query();

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name']);
        }

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'name', 'created_at', 'updated_at']
        );

        return $query->paginate($request->get('per_page', 15));
    }
}
