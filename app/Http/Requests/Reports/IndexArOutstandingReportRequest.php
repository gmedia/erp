<?php

namespace App\Http\Requests\Reports;

class IndexArOutstandingReportRequest extends AbstractArOutstandingReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->arOutstandingRules(),
            $this->indexPaginationRules(),
        );
    }
}
