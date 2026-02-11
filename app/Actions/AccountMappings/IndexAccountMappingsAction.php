<?php

namespace App\Actions\AccountMappings;

use App\Domain\AccountMappings\AccountMappingFilterService;
use App\Models\AccountMapping;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexAccountMappingsAction
{
    public function __construct(
        private AccountMappingFilterService $filterService
    ) {}

    public function execute(FormRequest $request): Collection|LengthAwarePaginator
    {
        $query = AccountMapping::query()->with([
            'sourceAccount.coaVersion',
            'targetAccount.coaVersion',
        ]);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, (string) $request->get('search'));
        }

        $this->filterService->applyAdvancedFilters($query, [
            'type' => $request->get('type'),
            'source_coa_version_id' => $request->get('source_coa_version_id'),
            'target_coa_version_id' => $request->get('target_coa_version_id'),
        ]);

        $sortBy = (string) $request->get('sort_by', 'created_at');
        $sortDirection = (string) $request->get('sort_direction', 'desc');

        $this->filterService->applySorting($query, $sortBy, $sortDirection);

        return $query->paginate($request->integer('per_page', 15));
    }
}
