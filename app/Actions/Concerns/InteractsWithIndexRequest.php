<?php

namespace App\Actions\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait InteractsWithIndexRequest
{
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
