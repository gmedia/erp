<?php

namespace App\Http\Requests\Reports;

abstract class AbstractStockMovementReportListingRequest extends AbstractReportRequest
{
    protected function stockMovementRules(): array
    {
        return array_merge(
            $this->searchRules(),
            $this->inventoryDimensionRules(),
            $this->dateRangeRules(),
            $this->sortByEnumRules([
                'product_name',
                'warehouse_name',
                'branch_name',
                'category_name',
                'product_category_name',
                'total_in',
                'total_out',
                'ending_balance',
                'last_moved_at',
            ]),
            $this->sortDirectionRules(),
        );
    }
}
