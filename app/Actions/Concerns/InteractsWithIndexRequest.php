<?php

namespace App\Actions\Concerns;

use Illuminate\Http\Request;

trait InteractsWithIndexRequest
{
    /**
     * @param  Request  $request
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
}