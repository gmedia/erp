<?php

namespace App\Http\Requests\Reports;

class IndexPurchaseOrderStatusReportRequest extends AbstractPurchaseOrderStatusReportRequest
{
    public function rules(): array
    {
        return array_merge($this->purchaseOrderStatusRules(), $this->indexPaginationRules());
    }
}
