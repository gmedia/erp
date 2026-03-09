<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class InventoryStocktakeVarianceReportCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = InventoryStocktakeVarianceReportResource::class;
}
