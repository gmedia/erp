<?php

namespace App\Http\Requests\StockMonitor;

class ExportStockMonitorRequest extends AbstractStockMonitorRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->stockMonitorRules(),
            [
                'format' => ['nullable', 'string', 'in:xlsx,csv'],
            ],
        );
    }
}
