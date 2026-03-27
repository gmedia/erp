<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function searchRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function dateRangeRules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    protected function sortByEnumRules(array $allowedSortFields): array
    {
        return [
            'sort_by' => ['nullable', 'string', Rule::in($allowedSortFields)],
        ];
    }

    protected function sortDirectionRules(): array
    {
        return [
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    protected function indexPaginationRules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'export' => ['nullable', 'boolean'],
        ];
    }

    protected function indexLimitRules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function exportFormatRules(): array
    {
        return [
            'format' => ['nullable', 'string', Rule::in(['xlsx', 'csv'])],
        ];
    }
}