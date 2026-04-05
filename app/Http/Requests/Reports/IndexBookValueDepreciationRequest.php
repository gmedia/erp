<?php

namespace App\Http\Requests\Reports;

class IndexBookValueDepreciationRequest extends AbstractBookValueDepreciationRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->bookValueDepreciationFilterRules(),
            $this->bookValueDepreciationSortRules(),
            $this->indexLimitRules(),
        );
    }
}
