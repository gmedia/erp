<?php

namespace App\Http\Resources\StockTransfers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class StockTransferCollection extends ResourceCollection
{
    /**
     * @var string
     */
    public $collects = StockTransferResource::class;
}
