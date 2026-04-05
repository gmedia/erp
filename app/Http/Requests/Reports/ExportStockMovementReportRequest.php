<?php

namespace App\Http\Requests\Reports;

class ExportStockMovementReportRequest extends AbstractStockMovementReportListingRequest
{
    public function rules(): array
    {
        return array_merge($this->stockMovementRules(), $this->exportFormatRules());
    }
}
