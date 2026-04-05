<?php

namespace App\Http\Requests\Reports;

abstract class AbstractInventoryValuationReportRequest extends AbstractReportRequest
{
    protected function inventoryValuationRules(): array
    {
        return array_merge(
            $this->searchRules(),
            $this->inventoryDimensionRules(),
            $this->sortByEnumRules([
                'product_name',
                'warehouse_name',
                'branch_name',
                'category_name',
                'quantity_on_hand',
                'average_cost',
                'stock_value',
                'moved_at',
            ]),
            $this->sortDirectionRules(),
        );
    }
}
