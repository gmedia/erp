<?php

namespace App\Http\Resources\StockAdjustments;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StockAdjustmentCollection extends ResourceCollection
{
    /**
     * @var string
     */
    public $collects = StockAdjustmentResource::class;
}
