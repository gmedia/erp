<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Requests\SimpleCrudUpdateRequest;

class UpdateWarehouseRequest extends SimpleCrudUpdateRequest
{
    use HasWarehouseRules;

    public function rules(): array
    {
        return $this->warehouseRules(true);
    }
}
