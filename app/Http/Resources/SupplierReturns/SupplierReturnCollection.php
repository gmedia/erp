<?php

namespace App\Http\Resources\SupplierReturns;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierReturnCollection extends ResourceCollection
{
    public $collects = SupplierReturnResource::class;
}
