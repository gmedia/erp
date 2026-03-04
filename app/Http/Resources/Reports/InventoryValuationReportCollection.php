<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InventoryValuationReportCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = InventoryValuationReportResource::class;
}
