<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\Warehouse;

class UpdateWarehouseRequest extends SimpleCrudUpdateRequest
{
    public function getModelClass(): string
    {
        return Warehouse::class;
    }
}
