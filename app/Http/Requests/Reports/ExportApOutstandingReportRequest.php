<?php

namespace App\Http\Requests\Reports;

class ExportApOutstandingReportRequest extends AbstractApOutstandingReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->apOutstandingRules(),
            $this->exportFormatRules(),
        );
    }
}
