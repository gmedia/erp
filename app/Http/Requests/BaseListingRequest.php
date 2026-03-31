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
    protected function listingSortRules(string $sortBy, bool $includeStringInSortDirection = true): array
    {
        $sortDirectionRules = ['nullable', 'in:asc,desc'];

        if ($includeStringInSortDirection) {
            $sortDirectionRules = ['nullable', 'string', 'in:asc,desc'];
        }

        return [
            'sort_by' => ['nullable', 'string', 'in:' . $sortBy],
            'sort_direction' => $sortDirectionRules,
        ];
    }
}