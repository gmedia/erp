<?php

namespace App\Http\Resources\Warehouses;

use App\Http\Resources\SimpleCrudCollection;

class WarehouseCollection extends SimpleCrudCollection
{
    public $collects = WarehouseResource::class;
}
