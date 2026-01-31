<?php

namespace App\Actions\FiscalYears;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Models\FiscalYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Action to retrieve paginated fiscal years with filtering and sorting.
 */
class IndexFiscalYearsAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return FiscalYear::class;
    }

    protected function getSortableFields(): array
    {
        return ['id', 'name', 'start_date', 'end_date', 'status', 'created_at', 'updated_at'];
    }

    /**
     * Execute the action to retrieve paginated entities with filters.
     *
     * @param  \Illuminate\Foundation\Http\FormRequest  $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function execute(FormRequest $request): LengthAwarePaginator
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        if ($request->filled('search')) {
            $this->applySearch($query, $request->get('search'), $this->getSearchFields());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $this->applySorting(
            $query,
            $request->get('sort_by', $this->getDefaultSortBy()),
            strtolower($request->get('sort_direction', $this->getDefaultSortDirection())) === 'asc' ? 'asc' : 'desc',
            $this->getSortableFields()
        );

        return $query->paginate($request->get('per_page', $this->getDefaultPerPage()));
    }
}
