<?php

namespace App\Http\Requests\StockTransfers;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transfer_number' => ['nullable', 'string', 'max:255', 'unique:stock_transfers,transfer_number'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'transfer_date' => ['required', 'date'],
            'expected_arrival_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:draft,pending_approval,approved,in_transit,received,cancelled'],
            'notes' => ['nullable', 'string'],
            'requested_by' => ['nullable', 'exists:employees,id'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.quantity_received' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
