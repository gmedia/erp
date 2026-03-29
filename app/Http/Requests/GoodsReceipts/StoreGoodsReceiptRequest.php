<?php

namespace App\Http\Requests\GoodsReceipts;

class StoreGoodsReceiptRequest extends AbstractGoodsReceiptRequest
{
    protected function grNumberUniqueRule(): string
    {
        return 'unique:goods_receipts,gr_number';
    }

    protected function usesSometimes(): bool
    {
        return false;
    }
}
