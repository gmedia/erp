<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StockAdjustmentReportCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = StockAdjustmentReportResource::class;
}
