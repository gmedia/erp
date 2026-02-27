<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\Warehouse;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends SimpleCrudStoreRequest
{
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')->where(
                    fn ($query) => $query->where('branch_id', $this->input('branch_id'))
                ),
            ],
            'name' => ['required', 'string', 'max:255', 'unique:warehouses,name'],
        ];
    }

    public function getModelClass(): string
    {
        return Warehouse::class;
    }
}
