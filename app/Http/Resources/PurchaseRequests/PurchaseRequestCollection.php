<?php

namespace App\Http\Resources\PurchaseRequests;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseRequestCollection extends ResourceCollection
{
    public $collects = PurchaseRequestResource::class;
}
