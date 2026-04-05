<?php

namespace App\Http\Requests\StockTransfers;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;

abstract class AbstractStockTransferRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;

    public function rules(): array
    {
        return [
            'transfer_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->buildTransferNumberUniqueRule(),
            ]),
            'from_warehouse_id' => $this->withSometimes(['required', 'exists:warehouses,id', 'different:to_warehouse_id']),
            'to_warehouse_id' => $this->withSometimes(['required', 'exists:warehouses,id']),
            'transfer_date' => $this->withSometimes(['required', 'date']),
            'expected_arrival_date' => $this->withSometimes(['nullable', 'date']),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,pending_approval,approved,in_transit,received,cancelled',
            ]),
            'notes' => $this->withSometimes(['nullable', 'string']),
            'requested_by' => $this->withSometimes(['nullable', 'exists:employees,id']),
            ...$this->extraRules(),

            'items' => $this->itemsRules(),
            'items.*.product_id' => [$this->itemRequiredRule(), 'exists:products,id'],
            'items.*.unit_id' => [$this->itemRequiredRule(), 'exists:units,id'],
            'items.*.quantity' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'items.*.quantity_received' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function extraRules(): array
    {
        return [];
    }

    protected function usesSometimes(): bool
    {
        return $this->isUpdateRequest();
    }

    private function buildTransferNumberUniqueRule(): string
    {
        if (! $this->isUpdateRequest()) {
            return 'unique:stock_transfers,transfer_number';
        }

        $transferId = $this->route('stockTransfer')->id ?? $this->route('id');

        return 'unique:stock_transfers,transfer_number,' . $transferId;
    }

    private function isUpdateRequest(): bool
    {
        return $this instanceof UpdateStockTransferRequest;
    }
}
