<?php

namespace App\Http\Requests\GoodsReceipts;

class ExportGoodsReceiptRequest extends AbstractGoodsReceiptListingRequest
{
    public function rules(): array
    {
        return $this->goodsReceiptListingRules(
            'gr_number,receipt_date,status,created_at',
            'purchase_order',
            'warehouse',
        );
    }
}
