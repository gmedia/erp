<?php

namespace App\Actions;

use App\Domain\DepartmentFilterService;
use App\Http\Requests\IndexDepartmentRequest;
use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexDepartmentsAction
{
    public function __construct(
        private DepartmentFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated departments with filters.
     *
     * @param IndexDepartmentRequest $request
     * @return LengthAwarePaginator
     */
    public function execute(IndexDepartmentRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

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
