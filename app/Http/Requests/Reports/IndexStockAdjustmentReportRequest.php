<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

class IndexStockAdjustmentReportRequest extends AbstractReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'adjustment_type' => [
                'nullable',
                'string',
                Rule::in([
                    'damage',
                    'expired',
                    'shrinkage',
                    'correction',
                    'stocktake_result',
                    'initial_stock',
                    'other',
                ]),
            ],
            'status' => ['nullable', 'string', Rule::in(['draft', 'pending_approval', 'approved', 'cancelled'])],
            ],
            $this->dateRangeRules(),
            $this->sortByEnumRules([
                'adjustment_date',
                'adjustment_type',
                'status',
                'warehouse_name',
                'branch_name',
                'total_quantity_adjusted',
                'total_adjustment_value',
                'adjustment_count',
            ]),
            $this->sortDirectionRules(),
            $this->indexPaginationRules(),
        );
    }
}
