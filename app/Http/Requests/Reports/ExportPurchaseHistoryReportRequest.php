<?php

namespace App\Http\Requests\Reports;

class ExportPurchaseHistoryReportRequest extends AbstractPurchaseHistoryReportRequest
{
    public function rules(): array
    {
        return array_merge($this->purchaseHistoryRules(), $this->exportFormatRules());
    }
}
