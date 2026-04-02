<?php

namespace App\Http\Requests\Reports;

class IndexInventoryStocktakeVarianceReportRequest extends AbstractInventoryStocktakeVarianceReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->inventoryStocktakeVarianceRules(),
            $this->indexPaginationRules(),
        );
    }
}
