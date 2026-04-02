<?php

namespace App\Http\Requests\Reports;

class ExportStockAdjustmentReportRequest extends AbstractStockAdjustmentReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->stockAdjustmentRules(),
            $this->exportFormatRules(),
        );
    }
}
