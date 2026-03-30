<?php

namespace App\Http\Requests\Reports;

class IndexBookValueDepreciationRequest extends AbstractReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'asset_category_id' => ['nullable', 'integer', 'exists:asset_categories,id'],
                'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            ],
            $this->sortByEnumRules([
                'asset_code',
                'name',
                'purchase_date',
                'purchase_cost',
                'book_value',
                'accumulated_depreciation',
            ]),
            $this->sortDirectionRules(),
            $this->indexLimitRules(),
        );
    }
}
