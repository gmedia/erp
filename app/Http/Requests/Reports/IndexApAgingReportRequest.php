<?php

namespace App\Http\Requests\Reports;

class IndexApAgingReportRequest extends AbstractApAgingReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->apAgingRules(),
            $this->indexPaginationRules(),
        );
    }
}
