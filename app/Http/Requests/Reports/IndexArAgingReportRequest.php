<?php

namespace App\Http\Requests\Reports;

class IndexArAgingReportRequest extends AbstractArAgingReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->arAgingRules(),
            $this->indexPaginationRules(),
        );
    }
}