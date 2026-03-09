<?php

namespace App\Http\Resources\StockTransfers;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class StockTransferCollection extends ResourceCollection
{
    /**
     * @var string
     */
    public $collects = StockTransferResource::class;
}
