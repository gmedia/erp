<?php

namespace App\Http\Requests\Reports;

class ExportArOutstandingReportRequest extends AbstractArOutstandingReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->arOutstandingRules(),
            $this->exportFormatRules(),
        );
    }
}