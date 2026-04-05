<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Requests\SimpleCrudExportRequest;

class ExportWarehouseRequest extends SimpleCrudExportRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules = array_merge($rules, $this->simpleCrudSortRulesByFields('id,code,name,branch,created_at,updated_at'));

        return array_merge($rules, [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);
    }
}
