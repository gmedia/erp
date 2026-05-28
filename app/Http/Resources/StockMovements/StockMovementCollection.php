<?php

namespace App\Http\Resources\StockMovements;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class StockMovementCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = StockMovementResource::class;
}
