<?php

namespace App\Http\Requests\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractSupplierListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function supplierListingRules(string $sortBy, bool $includeStringInSortDirection = true): array
    {
        $sortDirectionRules = ['nullable', 'in:asc,desc'];

        if ($includeStringInSortDirection) {
            $sortDirectionRules = ['nullable', 'string', 'in:asc,desc'];
        }

        return [
            'search' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'category_id' => ['nullable', 'exists:supplier_categories,id'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'sort_by' => ['nullable', 'string', 'in:' . $sortBy],
            'sort_direction' => $sortDirectionRules,
        ];
    }
}
