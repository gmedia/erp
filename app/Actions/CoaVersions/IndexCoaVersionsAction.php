<?php

namespace App\Actions\CoaVersions;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Domain\CoaVersions\CoaVersionFilterService;
use App\Models\CoaVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Action to retrieve paginated COA versions with filtering and sorting.
 */
class IndexCoaVersionsAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return CoaVersion::class;
    }

    protected function getSortableFields(): array
    {
        return ['id', 'name', 'fiscal_year_id', 'status', 'created_at', 'updated_at'];
    }

    public function __construct(
        private CoaVersionFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated entities with filters.
     *
     * @param  \Illuminate\Foundation\Http\FormRequest  $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function execute(FormRequest $request): LengthAwarePaginator
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query()->with('fiscalYear');

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), $this->getSearchFields());
        } else {
            $this->filterService->applyAdvancedFilters($query, [
                'status' => $request->get('status'),
                'fiscal_year_id' => $request->get('fiscal_year_id'),
            ]);
        }

        $sortBy = $request->get('sort_by', $this->getDefaultSortBy());
        $sortDirection = strtolower($request->get('sort_direction', $this->getDefaultSortDirection())) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'fiscal_year.name' || $sortBy === 'fiscal_year_name') {
            $query
                ->leftJoin('fiscal_years', 'coa_versions.fiscal_year_id', '=', 'fiscal_years.id')
                ->select('coa_versions.*')
                ->orderBy('fiscal_years.name', $sortDirection);
        } else {
            $this->filterService->applySorting(
                $query,
                $sortBy,
                $sortDirection,
                $this->getSortableFields()
            );
        }

        return $query->paginate($request->get('per_page', $this->getDefaultPerPage()));
    }
}
