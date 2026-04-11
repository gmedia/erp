<?php

namespace App\Actions\Accounts;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Domain\Accounts\AccountFilterService;
use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexAccountsAction extends SimpleCrudIndexAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private AccountFilterService $filterService
    ) {}

    /**
     * Execute the action to fetch accounts.
     */
    public function execute(FormRequest $request): Collection|LengthAwarePaginator
    {
        $query = Account::query()->with('parent');

        $this->applyCoaVersionFilter($request, $query);

        return $this->handleIndexRequestWithOptionalPagination(
            $request,
            $query,
            $this->filterService,
            $this->getSearchFields(),
            ['type', 'is_active', 'coa_version_id'],
            $this->getDefaultSortBy(),
            $this->getSortableFields(),
            'sort_order',
        );
    }

    protected function getModelClass(): string
    {
        return Account::class;
    }

    protected function getSearchFields(): array
    {
        return ['name', 'code'];
    }

    protected function getSortableFields(): array
    {
        return ['id', 'code', 'name', 'type', 'level', 'created_at', 'updated_at'];
    }

    private function applyCoaVersionFilter(FormRequest $request, Builder $query): void
    {
        if ($request->filled('coa_version_id')) {
            $query->where('coa_version_id', $request->get('coa_version_id'));

            return;
        }

        $activeVersionId = CoaVersion::query()
            ->where('status', 'active')
            ->value('id');

        if ($activeVersionId !== null) {
            $query->where('coa_version_id', $activeVersionId);
        }
    }
}
