<?php

namespace App\Http\Requests\StockMonitor;

class IndexStockMonitorRequest extends AbstractStockMonitorRequest
{
    public function rules(): array
    {
        return $this->stockMonitorIndexRules();
    }
}
