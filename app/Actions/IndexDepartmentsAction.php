<?php

namespace App\Actions;

use App\Http\Requests\IndexDepartmentRequest;
use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexDepartmentsAction
{
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

        $this->applySearch($query, $request, ['name']);
        $this->applySorting($query, $request, ['id', 'name', 'created_at', 'updated_at']);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Apply search filters to a query builder
     */
    private function applySearch($query, $request, array $searchFields): void
    {
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search, $searchFields) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }
    }

    /**
     * Apply sorting to a query builder
     */
    private function applySorting($query, $request, array $allowedSorts): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }
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
