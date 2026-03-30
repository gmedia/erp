<?php

namespace App\Http\Requests\StockMonitor;

class IndexStockMonitorRequest extends AbstractStockMonitorRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->stockMonitorRules(),
            [
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ],
        );
    }
}
