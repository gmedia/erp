<?php

namespace App\Http\Requests\Reports;

class ExportArAgingReportRequest extends AbstractArAgingReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->arAgingRules(),
            $this->exportFormatRules(),
        );
    }
}