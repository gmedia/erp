<?php

namespace App\Http\Requests\Reports;

abstract class AbstractBookValueDepreciationRequest extends AbstractReportRequest
{
    protected function bookValueDepreciationFilterRules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'asset_category_id' => ['nullable', 'integer', 'exists:asset_categories,id'],
                'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            ],
        );
    }

    protected function bookValueDepreciationSortRules(): array
    {
        return array_merge(
            $this->sortByEnumRules([
                'asset_code',
                'name',
                'purchase_date',
                'purchase_cost',
                'book_value',
                'accumulated_depreciation',
            ]),
            $this->sortDirectionRules(),
        );
    }
}
