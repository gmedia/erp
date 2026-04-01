<?php

namespace App\Http\Requests\SupplierReturns;

class IndexSupplierReturnRequest extends AbstractSupplierReturnListingRequest
{
    public function rules(): array
    {
        return $this->supplierReturnListingRules(
            'id,return_number,purchase_order,purchase_order_id,goods_receipt,goods_receipt_id,supplier,supplier_id,warehouse,warehouse_id,return_date,reason,status,created_at,updated_at',
            'purchase_order_id',
            'goods_receipt_id',
            'supplier_id',
            'warehouse_id',
            [
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
            ],
        );
    }
}
