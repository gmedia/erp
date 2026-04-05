<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Requests\SimpleCrudStoreRequest;

class StoreWarehouseRequest extends SimpleCrudStoreRequest
{
    use HasWarehouseRules;

    public function rules(): array
    {
        return $this->warehouseRules();
    }
}
