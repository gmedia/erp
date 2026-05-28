<?php

namespace App\Http\Resources\Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class InventoryStocktakeVarianceReportCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = InventoryStocktakeVarianceReportResource::class;
}
