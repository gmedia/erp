<?php

namespace App\Http\Requests\StockTransfers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $transferId = $this->route('stockTransfer')->id ?? $this->route('id');

        return [
            'transfer_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'unique:stock_transfers,transfer_number,' . $transferId,
            ],
            'from_warehouse_id' => ['sometimes', 'required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['sometimes', 'required', 'exists:warehouses,id'],
            'transfer_date' => ['sometimes', 'required', 'date'],
            'expected_arrival_date' => ['sometimes', 'nullable', 'date'],
            'status' => [
                'sometimes',
                'required',
                'string',
                'in:draft,pending_approval,approved,in_transit,received,cancelled',
            ],
            'notes' => ['sometimes', 'nullable', 'string'],
            'requested_by' => ['sometimes', 'nullable', 'exists:employees,id'],
            'approved_by' => ['sometimes', 'nullable', 'exists:users,id'],
            'approved_at' => ['sometimes', 'nullable', 'date'],
            'shipped_by' => ['sometimes', 'nullable', 'exists:users,id'],
            'shipped_at' => ['sometimes', 'nullable', 'date'],
            'received_by' => ['sometimes', 'nullable', 'exists:users,id'],
            'received_at' => ['sometimes', 'nullable', 'date'],

            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.unit_id' => ['required_with:items', 'exists:units,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.quantity_received' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
