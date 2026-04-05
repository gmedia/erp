<?php

namespace App\Http\Requests\Reports;

use App\Http\Requests\AuthorizedFormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractReportRequest extends AuthorizedFormRequest
{
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

    protected function supplierWarehouseProductRules(): array
    {
        return [
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }

    protected function inventoryDimensionRules(): array
    {
        return [
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
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
