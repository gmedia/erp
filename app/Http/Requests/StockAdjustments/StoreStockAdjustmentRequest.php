<?php

namespace App\Http\Requests\StockAdjustments;

class StoreStockAdjustmentRequest extends AbstractStockAdjustmentRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
