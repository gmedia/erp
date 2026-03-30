<?php

namespace App\Http\Requests\Reports;

class ExportPurchaseOrderStatusReportRequest extends AbstractPurchaseOrderStatusReportRequest
{
    public function rules(): array
    {
        return array_merge($this->purchaseOrderStatusRules(), $this->exportFormatRules());
    }
}
