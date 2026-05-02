<?php

namespace App\Http\Requests\Reports;

class IndexApOutstandingReportRequest extends AbstractApOutstandingReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->apOutstandingRules(),
            $this->indexPaginationRules(),
        );
    }
}
