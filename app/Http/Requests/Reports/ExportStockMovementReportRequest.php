<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

class ExportStockMovementReportRequest extends AbstractReportRequest
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
            $this->dateRangeRules(),
            $this->sortByEnumRules([
                'product_name',
                'warehouse_name',
                'branch_name',
                'category_name',
                'product_category_name',
                'total_in',
                'total_out',
                'ending_balance',
                'last_moved_at',
            ]),
            $this->sortDirectionRules(),
            $this->exportFormatRules(),
        );
    }
}
