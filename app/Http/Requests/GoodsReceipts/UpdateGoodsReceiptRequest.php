<?php

namespace App\Http\Requests\GoodsReceipts;

class UpdateGoodsReceiptRequest extends AbstractGoodsReceiptRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
