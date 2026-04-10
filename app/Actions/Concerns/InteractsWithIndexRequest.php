<?php

namespace App\Actions\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

trait InteractsWithIndexRequest
{
    use InteractsWithExportableQuery;

    /**
     * @return array{perPage: int, page: int}
     */
    private function getPaginationParams(Request $request): array
    {
        return [
            'perPage' => (int) $request->get('per_page', 15),
            'page' => (int) $request->get('page', 1),
        ];
    }

    private function normalizeSortDirection(?string $sortDirection): string
    {
        return strtolower((string) $sortDirection) === 'asc' ? 'asc' : 'desc';
    }

    /**
     * @param  object{applySearch: callable}  $filterService
     * @param  array<int, string>  $searchFields
     */
    private function applyRequestSearch(Request $request, Builder $query, object $filterService, array $searchFields): void
    {
        if (! $request->filled('search')) {
            return;
        }

        $filterService->applySearch($query, $request->string('search')->toString(), $searchFields);
    }

    /**
     * @param  object{applySearch: callable, applyAdvancedFilters: callable}  $filterService
     * @param  array<int, string>  $searchFields
     * @param  array<int, string>  $primaryFilterKeys
     */
    private function applySearchOrPrimaryFilters(
        Request $request,
        Builder $query,
        object $filterService,
        array $searchFields,
        array $primaryFilterKeys
    ): void {
        if ($request->filled('search')) {
            $filterService->applySearch($query, $request->get('search'), $searchFields);

            return;
        }

        $filterService->applyAdvancedFilters($query, $this->extractRequestFilters($request, $primaryFilterKeys));
    }

    /**
     * @param  object{applyAdvancedFilters: callable}  $filterService
     * @param  array<int, string>  $filterKeys
     */
    private function applyRequestFilters(Request $request, Builder $query, object $filterService, array $filterKeys): void
    {
        $filterService->applyAdvancedFilters($query, $this->extractRequestFilters($request, $filterKeys));
    }

    private function excludeStatusWhenFilterMissing(
        Request $request,
        Builder $query,
        string $excludedStatus,
        string $statusField = 'status'
    ): void {
        if ($request->filled($statusField)) {
            return;
        }

        $query->where($statusField, '!=', $excludedStatus);
    }

    /**
     * @param  object{applySorting: callable}  $filterService
     * @param  array<int, string>  $allowedSorts
     */
    private function applyIndexSorting(
        Request $request,
        Builder $query,
        object $filterService,
        string $defaultSortBy,
        array $allowedSorts
    ): void {
        $filterService->applySorting(
            $query,
            $request->get('sort_by', $defaultSortBy),
            $this->normalizeSortDirection($request->get('sort_direction', 'desc')),
            $allowedSorts,
        );
    }

    /**
     * @param  object{applySearch: callable, applyAdvancedFilters: callable, applySorting: callable}  $filterService
     * @param  array<int, string>  $searchFields
     * @param  array<int, string>  $filterKeys
     * @param  array<int, string>  $allowedSorts
     */
    private function handleIndexRequestWithStatusExclusion(
        Request $request,
        Builder $query,
        object $filterService,
        array $searchFields,
        array $filterKeys,
        string $defaultSortBy,
        array $allowedSorts,
        string $excludedStatus,
        string $statusField = 'status'
    ): LengthAwarePaginator {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $this->applyRequestSearch($request, $query, $filterService, $searchFields);
        $this->excludeStatusWhenFilterMissing($request, $query, $excludedStatus, $statusField);
        $this->applyRequestFilters($request, $query, $filterService, $filterKeys);
        $this->applyIndexSorting($request, $query, $filterService, $defaultSortBy, $allowedSorts);

        return $this->paginateIndexQuery($query, $perPage, $page);
    }

    /**
     * @param  object{applySearch: callable, applyAdvancedFilters: callable, applySorting: callable}  $filterService
     * @param  array<int, string>  $searchFields
     * @param  array<int, string>  $primaryFilterKeys
     * @param  array<int, string>  $filterKeys
     * @param  array<int, string>  $allowedSorts
     */
    private function handleSearchOrPrimaryIndexRequest(
        Request $request,
        Builder $query,
        object $filterService,
        array $searchFields,
        array $primaryFilterKeys,
        array $filterKeys,
        string $defaultSortBy,
        array $allowedSorts
    ): LengthAwarePaginator {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $this->applySearchOrPrimaryFilters($request, $query, $filterService, $searchFields, $primaryFilterKeys);

        if ($filterKeys !== []) {
            $this->applyRequestFilters($request, $query, $filterService, $filterKeys);
        }

        $this->applyIndexSorting($request, $query, $filterService, $defaultSortBy, $allowedSorts);

        return $this->paginateIndexQuery($query, $perPage, $page);
    }

    /**
     * @param  object{applySearch: callable, applyAdvancedFilters: callable, applySorting: callable}  $filterService
     * @param  array<int, string>  $searchFields
     * @param  array<int, string>  $filterKeys
     * @param  array<int, string>  $allowedSorts
     */
    private function handleIndexRequest(
        Request $request,
        Builder $query,
        object $filterService,
        array $searchFields,
        array $filterKeys,
        string $defaultSortBy,
        array $allowedSorts
    ): LengthAwarePaginator {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $this->applyRequestSearch($request, $query, $filterService, $searchFields);
        $this->applyRequestFilters($request, $query, $filterService, $filterKeys);
        $this->applyIndexSorting($request, $query, $filterService, $defaultSortBy, $allowedSorts);

        return $this->paginateIndexQuery($query, $perPage, $page);
    }

    /**
     * @param  object{applySearch: callable, applyAdvancedFilters: callable, applySorting: callable}  $filterService
     * @param  array<int, string>  $searchFields
     * @param  array<int, string>  $filterKeys
     * @param  array<int, string>  $allowedSorts
     */
    private function handleIndexRequestWithOptionalExport(
        Request $request,
        Builder $query,
        object $filterService,
        array $searchFields,
        array $filterKeys,
        string $defaultSortBy,
        array $allowedSorts
    ): LengthAwarePaginator|Collection {
        ['page' => $page] = $this->getPaginationParams($request);

        $this->applyRequestSearch($request, $query, $filterService, $searchFields);
        $this->applyRequestFilters($request, $query, $filterService, $filterKeys);
        $this->applyIndexSorting($request, $query, $filterService, $defaultSortBy, $allowedSorts);

        return $this->exportOrPaginate($request, $query, $page);
    }

    /**
     * @param  object{applySorting: callable}  $filterService
     * @param  array<int, string>  $allowedSorts
     * @param  array<string, string>  $sortMap
     */
    private function applyMappedIndexSorting(
        Request $request,
        Builder $query,
        object $filterService,
        string $defaultSortBy,
        array $allowedSorts,
        array $sortMap
    ): void {
        $sortBy = $request->string('sort_by', $defaultSortBy)->toString();

        $filterService->applySorting(
            $query,
            $sortMap[$sortBy] ?? $sortBy,
            $this->normalizeSortDirection($request->string('sort_direction', 'desc')->toString()),
            $allowedSorts,
        );
    }

    /**
     * @param  object{applySearch: callable, applyAdvancedFilters: callable, applySorting: callable}  $filterService
     * @param  array<int, string>  $searchFields
     * @param  array<int, string>  $filterKeys
     * @param  array<int, string>  $allowedSorts
     * @param  array<string, string>  $sortMap
     */
    private function handleMappedIndexRequest(
        Request $request,
        Builder $query,
        object $filterService,
        array $searchFields,
        array $filterKeys,
        string $defaultSortBy,
        array $allowedSorts,
        array $sortMap
    ): LengthAwarePaginator {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $this->applyRequestSearch($request, $query, $filterService, $searchFields);
        $this->applyRequestFilters($request, $query, $filterService, $filterKeys);
        $this->applyMappedIndexSorting($request, $query, $filterService, $defaultSortBy, $allowedSorts, $sortMap);

        return $this->paginateIndexQuery($query, $perPage, $page);
    }

    private function paginateIndexQuery(Builder $query, int $perPage, int $page): LengthAwarePaginator
    {
        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param  array<int, string>  $filterKeys
     * @return array<string, mixed>
     */
    private function extractRequestFilters(Request $request, array $filterKeys): array
    {
        $filters = [];

        foreach ($filterKeys as $filterKey) {
            $filters[$filterKey] = $request->get($filterKey);
        }

        return $filters;
    }
}
