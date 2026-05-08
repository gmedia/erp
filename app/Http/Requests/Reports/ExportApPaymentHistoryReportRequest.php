<?php

namespace App\Http\Requests\Reports;

class ExportApPaymentHistoryReportRequest extends AbstractApPaymentHistoryReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->apPaymentHistoryRules(),
            $this->exportFormatRules(),
        );
    }
}
