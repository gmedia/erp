<?php

namespace App\Actions\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

trait InteractsWithExportableQuery
{
    protected function exportOrPaginate(
        Request $request,
        Builder $query,
        ?int $page = null,
        bool $withQueryString = true,
        int $defaultPerPage = 15,
    ): LengthAwarePaginator|Collection {
        if ($request->boolean('export')) {
            return $query->get();
        }

        $perPage = (int) $request->get('per_page', $defaultPerPage);
        $result = $page === null
            ? $query->paginate($perPage)
            : $query->paginate($perPage, ['*'], 'page', $page);

        if (! $withQueryString) {
            return $result;
        }

        return $result->withQueryString();
    }
}
