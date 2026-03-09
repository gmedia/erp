<?php

namespace App\Http\Resources\Suppliers;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class SupplierCollection extends ResourceCollection
{
    /**
     * The resource that this collection transforms.
     *
     * @var string
     */
    public $collects = SupplierResource::class;
}
