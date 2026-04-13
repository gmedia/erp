<?php

namespace App\Http\Requests\GoodsReceipts;

class StoreGoodsReceiptRequest extends AbstractGoodsReceiptRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
