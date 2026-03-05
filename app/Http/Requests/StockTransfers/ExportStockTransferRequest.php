<?php

namespace App\Http\Requests\StockTransfers;

use Illuminate\Foundation\Http\FormRequest;

class ExportStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'from_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'to_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'status' => ['nullable', 'string', 'in:draft,pending_approval,approved,in_transit,received,cancelled'],
            'transfer_date_from' => ['nullable', 'date'],
            'transfer_date_to' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'string', 'in:id,transfer_number,from_warehouse_id,to_warehouse_id,transfer_date,expected_arrival_date,status,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
