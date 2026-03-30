<?php

namespace App\Http\Requests\Reports;

class IndexPurchaseHistoryReportRequest extends AbstractPurchaseHistoryReportRequest
{
    public function rules(): array
    {
        return array_merge($this->purchaseHistoryRules(), $this->indexPaginationRules());
    }
}
