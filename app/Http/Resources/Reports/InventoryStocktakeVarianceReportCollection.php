<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InventoryStocktakeVarianceReportCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = InventoryStocktakeVarianceReportResource::class;
}
