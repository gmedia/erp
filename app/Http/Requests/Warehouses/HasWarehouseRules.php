<?php

namespace App\Http\Requests\Warehouses;

use Illuminate\Validation\Rule;

trait HasWarehouseRules
{
    public function warehouseRules(bool $isUpdate = false): array
    {
        $warehouseId = $this->route('warehouse')->id ?? $this->route('id');
        $currentBranchId = $this->route('warehouse')?->branch_id;

        $branchIdRules = ['required', 'integer', 'exists:branches,id'];
        $codeRules = [
            'required',
            'string',
            'max:50',
            Rule::unique('warehouses', 'code')->where(
                fn ($query) => $query->where('branch_id', $this->input('branch_id', $currentBranchId))
            ),
        ];
        $nameRules = ['required', 'string', 'max:255', Rule::unique('warehouses', 'name')];

        if ($isUpdate) {
            $branchIdRules = ['sometimes', ...$branchIdRules];
            $codeRules = ['sometimes', ...$codeRules];
            $nameRules = ['sometimes', ...$nameRules];

            $codeRules[count($codeRules) - 1] = $codeRules[count($codeRules) - 1]->ignore($warehouseId);
            $nameRules[count($nameRules) - 1] = $nameRules[count($nameRules) - 1]->ignore($warehouseId);
        }

        return [
            'branch_id' => $branchIdRules,
            'code' => $codeRules,
            'name' => $nameRules,
        ];
    }
}
