<?php

namespace App\Http\Requests\StockTransfers;

class UpdateStockTransferRequest extends AbstractStockTransferRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function extraRules(): array
    {
        // Issue #436: approved/shipped/received_* diisi server saat transisi status.
        return [];
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
