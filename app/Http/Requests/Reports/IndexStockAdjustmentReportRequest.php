<?php

namespace App\Http\Requests\Reports;

class IndexStockAdjustmentReportRequest extends AbstractStockAdjustmentReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->stockAdjustmentRules(),
            $this->indexPaginationRules(),
        );
    }
}
