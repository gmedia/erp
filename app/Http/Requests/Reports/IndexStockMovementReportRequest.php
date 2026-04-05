<?php

namespace App\Http\Requests\Reports;

class IndexStockMovementReportRequest extends AbstractStockMovementReportListingRequest
{
    public function rules(): array
    {
        return array_merge($this->stockMovementRules(), $this->indexPaginationRules());
    }
}
