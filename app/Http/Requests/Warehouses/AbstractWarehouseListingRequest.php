<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Requests\SimpleCrudListingRequest;

abstract class AbstractWarehouseListingRequest extends SimpleCrudListingRequest
{
    protected function warehouseListingRules(): array
    {
        return array_merge(
            $this->searchRules(),
            $this->simpleCrudSortRulesByFields('id,code,name,branch,created_at,updated_at'),
            [
                'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            ],
        );
    }
}
