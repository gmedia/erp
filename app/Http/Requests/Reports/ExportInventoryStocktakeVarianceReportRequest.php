<?php

namespace App\Http\Requests\Reports;

class ExportInventoryStocktakeVarianceReportRequest extends AbstractInventoryStocktakeVarianceReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->inventoryStocktakeVarianceRules(),
            $this->exportFormatRules(),
        );
    }
}
