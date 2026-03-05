<?php

namespace App\Http\Resources\StockTransfers;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StockTransferCollection extends ResourceCollection
{
    /**
     * @var string
     */
    public $collects = StockTransferResource::class;
}
