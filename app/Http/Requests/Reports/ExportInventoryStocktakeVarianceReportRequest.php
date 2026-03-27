<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

class ExportInventoryStocktakeVarianceReportRequest extends AbstractReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
            'inventory_stocktake_id' => ['nullable', 'integer', 'exists:inventory_stocktakes,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'result' => ['nullable', 'string', Rule::in(['surplus', 'deficit'])],
            ],
            $this->dateRangeRules(),
            $this->sortByEnumRules([
                'stocktake_number',
                'stocktake_date',
                'product_name',
                'product_code',
                'category_name',
                'warehouse_name',
                'branch_name',
                'system_quantity',
                'counted_quantity',
                'variance',
                'result',
                'counted_at',
                'counted_by_name',
            ]),
            $this->sortDirectionRules(),
            $this->exportFormatRules(),
        );
    }
}
