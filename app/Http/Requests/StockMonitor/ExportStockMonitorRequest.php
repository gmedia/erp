<?php

namespace App\Http\Requests\StockMonitor;

class ExportStockMonitorRequest extends AbstractStockMonitorRequest
{
    public function rules(): array
    {
        return $this->stockMonitorExportRules();
    }
}
