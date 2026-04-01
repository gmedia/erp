<?php

namespace App\Http\Requests\GoodsReceipts;

class IndexGoodsReceiptRequest extends AbstractGoodsReceiptListingRequest
{
    public function rules(): array
    {
        return $this->goodsReceiptListingRules(
            'id,gr_number,purchase_order,purchase_order_id,warehouse,warehouse_id,receipt_date,supplier_delivery_note,status,created_at,updated_at',
            'purchase_order_id',
            'warehouse_id',
            [
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
            ],
        );
    }
}
