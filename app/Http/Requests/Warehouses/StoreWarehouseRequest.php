<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\Warehouse;

class StoreWarehouseRequest extends SimpleCrudStoreRequest
{
    public function getModelClass(): string
    {
        return Warehouse::class;
    }
}
