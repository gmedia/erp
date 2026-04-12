<?php

namespace App\Actions\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Http\FormRequest;

trait ExecutesConfiguredMappedIndexRequest
{
    use InteractsWithIndexRequest;

    /**
     * @param  object{applySearch: callable, applyAdvancedFilters: callable, applySorting: callable}  $filterService
     * @param  array{
     *      model_class: class-string<\Illuminate\Database\Eloquent\Model>,
     *      with: array<int, string>,
     *      search_fields: array<int, string>,
     *      filter_keys: array<int, string>,
     *      default_sort_by: string,
     *      allowed_sorts: array<int, string>,
     *      sort_map: array<string, string>
     *  }  $configuration
     */
    protected function executeConfiguredMappedIndexRequest(
        FormRequest $request,
        object $filterService,
        array $configuration
    ): LengthAwarePaginator {
        $modelClass = $configuration['model_class'];
        $query = $modelClass::query()->with($configuration['with']);

        return $this->handleMappedIndexRequest(
            $request,
            $query,
            $filterService,
            $configuration['search_fields'],
            $configuration['filter_keys'],
            $configuration['default_sort_by'],
            $configuration['allowed_sorts'],
            $configuration['sort_map'],
        );
    }
}
