<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StockMovementReportCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = StockMovementReportResource::class;
}
