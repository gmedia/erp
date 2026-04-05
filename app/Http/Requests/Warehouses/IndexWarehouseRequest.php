<?php

namespace App\Http\Requests\Warehouses;

class IndexWarehouseRequest extends AbstractWarehouseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->warehouseListingRules(),
            $this->paginationRules(),
        );
    }
}
