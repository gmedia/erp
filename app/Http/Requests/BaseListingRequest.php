<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function searchRules(): array
    {
        return [
            'search' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function paginationRules(): array
    {
        return array_merge(
            $this->perPageRules(),
            [
                'page' => ['nullable', 'integer', 'min:1'],
            ],
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function perPageRules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function listingSortRules(
        string $sortBy,
        bool $includeStringInSortDirection = true,
        string $sortDirectionField = 'sort_direction'
    ): array {
        $sortDirectionRules = ['nullable', 'in:asc,desc'];

        if ($includeStringInSortDirection) {
            $sortDirectionRules = ['nullable', 'string', 'in:asc,desc'];
        }

        return [
            'sort_by' => ['nullable', 'string', 'in:' . $sortBy],
            $sortDirectionField => $sortDirectionRules,
        ];
    }
}
