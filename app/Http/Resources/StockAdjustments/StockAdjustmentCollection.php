<?php

namespace App\Http\Resources\StockAdjustments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class StockAdjustmentCollection extends ResourceCollection
{
    /**
     * @var string
     */
    public $collects = StockAdjustmentResource::class;
}
