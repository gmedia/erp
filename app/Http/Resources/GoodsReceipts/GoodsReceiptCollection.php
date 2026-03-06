<?php

namespace App\Http\Resources\GoodsReceipts;

use Illuminate\Http\Resources\Json\ResourceCollection;

class GoodsReceiptCollection extends ResourceCollection
{
    public $collects = GoodsReceiptResource::class;
}
