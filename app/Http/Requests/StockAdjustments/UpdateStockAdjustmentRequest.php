<?php

namespace App\Http\Requests\StockAdjustments;

class UpdateStockAdjustmentRequest extends AbstractStockAdjustmentRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
