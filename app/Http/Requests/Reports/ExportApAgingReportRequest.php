<?php

namespace App\Http\Requests\Reports;

class ExportApAgingReportRequest extends AbstractApAgingReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->apAgingRules(),
            $this->exportFormatRules(),
        );
    }
}
