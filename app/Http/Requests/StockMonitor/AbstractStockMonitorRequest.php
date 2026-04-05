<?php

namespace App\Http\Requests\StockMonitor;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractStockMonitorRequest extends BaseListingRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function searchRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function stockMonitorRules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'product_id' => ['nullable', 'integer', 'exists:products,id'],
                'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
                'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
                'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
                'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            ],
            $this->listingSortRules(
                'product_name,warehouse_name,category_name,quantity_on_hand,average_cost,stock_value,moved_at'
            ),
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function stockMonitorIndexRules(): array
    {
        return array_merge(
            $this->stockMonitorRules(),
            $this->perPageRules(),
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function stockMonitorExportRules(): array
    {
        return array_merge(
            $this->stockMonitorRules(),
            [
                'format' => ['nullable', 'string', 'in:xlsx,csv'],
            ],
        );
    }
}
