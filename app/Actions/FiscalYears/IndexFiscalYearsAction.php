<?php

namespace App\Actions\FiscalYears;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Domain\FiscalYears\FiscalYearFilterService;
use App\Models\FiscalYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Action to retrieve paginated fiscal years with filtering and sorting.
 */
class IndexFiscalYearsAction extends SimpleCrudIndexAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private FiscalYearFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated entities with filters.
     */
    public function execute(FormRequest $request): LengthAwarePaginator
    {
        $query = FiscalYear::query();

        return $this->handleSearchOrPrimaryIndexRequest(
            $request,
            $query,
            $this->filterService,
            $this->getSearchFields(),
            ['status'],
            [],
            $this->getDefaultSortBy(),
            $this->getSortableFields(),
        );
    }

    protected function getModelClass(): string
    {
        return FiscalYear::class;
    }

    protected function getSortableFields(): array
    {
        return ['id', 'name', 'start_date', 'end_date', 'status', 'created_at', 'updated_at'];
    }
}
