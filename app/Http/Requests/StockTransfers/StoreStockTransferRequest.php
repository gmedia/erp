<?php

namespace App\Http\Requests\StockTransfers;

class StoreStockTransferRequest extends AbstractStockTransferRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
