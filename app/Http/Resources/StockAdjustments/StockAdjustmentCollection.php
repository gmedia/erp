<?php

namespace App\Http\Resources\StockAdjustments;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class StockAdjustmentCollection extends ResourceCollection
{
    /**
     * @var string
     */
    public $collects = StockAdjustmentResource::class;
}
