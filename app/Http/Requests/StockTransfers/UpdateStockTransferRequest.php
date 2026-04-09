<?php

namespace App\Http\Requests\StockTransfers;

class UpdateStockTransferRequest extends AbstractStockTransferRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function extraRules(): array
    {
        return [
            'approved_by' => ['sometimes', 'nullable', 'exists:users,id'],
            'approved_at' => ['sometimes', 'nullable', 'date'],
            'shipped_by' => ['sometimes', 'nullable', 'exists:users,id'],
            'shipped_at' => ['sometimes', 'nullable', 'date'],
            'received_by' => ['sometimes', 'nullable', 'exists:users,id'],
            'received_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
