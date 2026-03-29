<?php

namespace App\Http\Requests\GoodsReceipts;

use Illuminate\Validation\Rule;

class UpdateGoodsReceiptRequest extends AbstractGoodsReceiptRequest
{
    protected function grNumberUniqueRule(): object
    {
        return Rule::unique('goods_receipts', 'gr_number')->ignore($this->route('goodsReceipt')?->id);
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
