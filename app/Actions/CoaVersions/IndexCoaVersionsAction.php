<?php

namespace App\Actions\CoaVersions;

use App\Actions\Concerns\InteractsWithIndexRequest;
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
    use InteractsWithIndexRequest;

    public function __construct(
        private CoaVersionFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated entities with filters.
     */
    public function execute(FormRequest $request): LengthAwarePaginator
    {
        $query = CoaVersion::query()->with('fiscalYear');

        return $this->handleSearchOrPrimaryIndexRequest(
            $request,
            $query,
            $this->filterService,
            $this->getSearchFields(),
            ['status', 'fiscal_year_id'],
            [],
            $this->getDefaultSortBy(),
            $this->getSortableFields(),
        );
    }

    protected function getModelClass(): string
    {
        return CoaVersion::class;
    }

    protected function getSortableFields(): array
    {
        return ['id', 'name', 'fiscal_year_id', 'fiscal_year.name', 'fiscal_year_name', 'status', 'created_at', 'updated_at'];
    }
}
