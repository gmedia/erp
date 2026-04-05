<?php

namespace App\Http\Requests\Reports;

class ExportInventoryValuationReportRequest extends AbstractInventoryValuationReportRequest
{
    public function rules(): array
    {
        return array_merge($this->inventoryValuationRules(), $this->exportFormatRules());
    }
}
