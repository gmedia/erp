<?php

namespace App\Http\Requests\Reports;

class ExportBookValueDepreciationRequest extends AbstractBookValueDepreciationRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->bookValueDepreciationFilterRules(),
            [
                'sort_by' => ['nullable', 'string'],
            ],
            $this->sortDirectionRules(),
        );
    }
}
