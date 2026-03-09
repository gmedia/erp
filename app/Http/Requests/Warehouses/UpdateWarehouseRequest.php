<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\Warehouse;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends SimpleCrudUpdateRequest
{
    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')->id ?? $this->route('id');
        $currentBranchId = $this->route('warehouse')?->branch_id;

        return [
            'branch_id' => ['sometimes', 'required', 'integer', 'exists:branches,id'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')
                    ->where(fn ($query) => $query->where(
                        'branch_id',
                        $this->input('branch_id', $currentBranchId),
                    ))
                    ->ignore($warehouseId),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')->ignore($warehouseId),
            ],
        ];
    }

    public function getModelClass(): string
    {
        return Warehouse::class;
    }
}
