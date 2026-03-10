<?php

namespace App\Http\Resources\PurchaseOrders;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseOrderCollection extends ResourceCollection
{
    public $collects = PurchaseOrderResource::class;
}
