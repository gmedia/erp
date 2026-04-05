<?php

namespace App\Http\Requests\Reports;

class IndexInventoryValuationReportRequest extends AbstractInventoryValuationReportRequest
{
    public function rules(): array
    {
        return array_merge($this->inventoryValuationRules(), $this->indexPaginationRules());
    }
}
