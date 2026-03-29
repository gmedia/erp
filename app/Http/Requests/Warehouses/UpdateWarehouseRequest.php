<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\Warehouse;

class UpdateWarehouseRequest extends SimpleCrudUpdateRequest
{
    use HasWarehouseRules;

    public function rules(): array
    {
        return $this->warehouseRules(true);
    }

    public function getModelClass(): string
    {
        return Warehouse::class;
    }
}
