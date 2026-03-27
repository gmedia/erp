<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

class IndexInventoryValuationReportRequest extends AbstractReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            ],
            $this->sortByEnumRules([
                'product_name',
                'warehouse_name',
                'branch_name',
                'category_name',
                'quantity_on_hand',
                'average_cost',
                'stock_value',
                'moved_at',
            ]),
            $this->sortDirectionRules(),
            $this->indexPaginationRules(),
        );
    }
}
