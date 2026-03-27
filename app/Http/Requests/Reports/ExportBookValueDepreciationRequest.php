<?php

namespace App\Http\Requests\Reports;

class ExportBookValueDepreciationRequest extends AbstractReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'asset_category_id' => ['nullable', 'integer', 'exists:asset_categories,id'],
                'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
                'sort_by' => ['nullable', 'string'],
            ],
            $this->sortDirectionRules(),
        );
    }
}
