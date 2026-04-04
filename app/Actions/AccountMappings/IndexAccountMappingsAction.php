<?php

namespace App\Actions\AccountMappings;

use App\Actions\AccountMappings\Concerns\BuildsAccountMappingQuery;
use App\Domain\AccountMappings\AccountMappingFilterService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexAccountMappingsAction
{
    use BuildsAccountMappingQuery;

    public function __construct(
        private AccountMappingFilterService $filterService
    ) {}

    public function execute(FormRequest $request): Collection|LengthAwarePaginator
    {
        $query = $this->buildAccountMappingQuery(
            $this->filterService,
            [
                'type' => $request->get('type'),
                'source_coa_version_id' => $request->get('source_coa_version_id'),
                'target_coa_version_id' => $request->get('target_coa_version_id'),
            ],
            $request->filled('search') ? (string) $request->get('search') : null,
            (string) $request->get('sort_by', 'created_at'),
            (string) $request->get('sort_direction', 'desc'),
        );

        return $query->paginate($request->integer('per_page', 15));
    }
}
