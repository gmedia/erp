<?php

namespace App\Http\Resources\StockMovements;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StockMovementCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = StockMovementResource::class;
}
