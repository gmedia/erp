<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\Warehouse;

class StoreWarehouseRequest extends SimpleCrudStoreRequest
{
    use HasWarehouseRules;

    public function rules(): array
    {
        return $this->warehouseRules();
    }

    public function getModelClass(): string
    {
        return Warehouse::class;
    }
}
