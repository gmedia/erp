<?php

namespace App\Actions\Accounts;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Domain\Accounts\AccountFilterService;
use App\Http\Requests\Accounts\IndexAccountRequest;
use App\Models\Account;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexAccountsAction extends SimpleCrudIndexAction
{
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

    public function __construct(
        private AccountFilterService $filterService
    ) {}

    /**
     * Execute the action to fetch accounts.
     * 
     * @param  \Illuminate\Foundation\Http\FormRequest  $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function execute(FormRequest $request): Collection|LengthAwarePaginator
    {
        $query = Account::query()->with('parent');

        // Default to active COA version if not provided
        if ($request->filled('coa_version_id')) {
            $query->where('coa_version_id', $request->coa_version_id);
        } else {
            // Find active coa version
            $activeVersion = \App\Models\CoaVersion::where('status', 'active')->first();
            if ($activeVersion) {
                $query->where('coa_version_id', $activeVersion->id);
            }
        }

        // Apply filters AND search
        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), $this->getSearchFields());
        }
        
        $this->filterService->applyAdvancedFilters($query, [
            'type' => $request->get('type'),
            'is_active' => $request->get('is_active'),
            'coa_version_id' => $request->get('coa_version_id'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', $this->getDefaultSortBy()),
            strtolower($request->get('sort_direction', $this->getDefaultSortDirection())) === 'asc' ? 'asc' : 'desc',
            $this->getSortableFields()
        );

        // If per_page is provided, paginate. Otherwise return all (useful for tree)
        if ($request->filled('per_page')) {
            return $query->paginate($request->integer('per_page'));
        }

        return $query->get();
    }
}
