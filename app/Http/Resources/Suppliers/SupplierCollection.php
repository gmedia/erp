<?php

namespace App\Http\Resources\Suppliers;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierCollection extends ResourceCollection
{
    /**
     * The resource that this collection transforms.
     *
     * @var string
     */
    public $collects = SupplierResource::class;
}
