<?php

namespace App\Http\Requests\Warehouses;

class ExportWarehouseRequest extends AbstractWarehouseListingRequest
{
    public function rules(): array
    {
        return $this->warehouseListingRules();
    }
}
