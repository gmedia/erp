<?php

namespace App\Http\Requests\StockAdjustments;

use App\Http\Requests\Concerns\HasSometimesArrayRules;
use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractStockAdjustmentRequest extends FormRequest
{
    use HasSometimesArrayRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->buildAdjustmentNumberUniqueRule(),
            ]),
            'warehouse_id' => $this->withSometimes(['required', 'exists:warehouses,id']),
            'adjustment_date' => $this->withSometimes(['required', 'date']),
            'adjustment_type' => $this->withSometimes([
                'required',
                'string',
                'in:damage,expired,shrinkage,correction,stocktake_result,initial_stock,other',
            ]),
            'status' => $this->withSometimes(['required', 'string', 'in:draft,pending_approval,approved,cancelled']),
            'inventory_stocktake_id' => $this->withSometimes(['nullable', 'exists:inventory_stocktakes,id']),
            'notes' => $this->withSometimes(['nullable', 'string']),
            'journal_entry_id' => $this->withSometimes(['nullable', 'exists:journal_entries,id']),
            'approved_by' => $this->withSometimes(['nullable', 'exists:users,id']),
            'approved_at' => $this->withSometimes(['nullable', 'date']),

            'items' => $this->itemsRules(),
            'items.*.product_id' => [$this->itemRequiredRule(), 'exists:products,id'],
            'items.*.unit_id' => [$this->itemRequiredRule(), 'exists:units,id'],
            'items.*.quantity_before' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity_adjusted' => [$this->itemRequiredRule(), 'numeric', 'not_in:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string'],
        ];
    }

    private function buildAdjustmentNumberUniqueRule(): string
    {
        if (! $this->isUpdateRequest()) {
            return 'unique:stock_adjustments,adjustment_number';
        }

        $adjustmentId = $this->route('stockAdjustment')->id ?? $this->route('id');

        return 'unique:stock_adjustments,adjustment_number,' . $adjustmentId;
    }

    private function isUpdateRequest(): bool
    {
        return $this instanceof UpdateStockAdjustmentRequest;
    }

    protected function usesSometimes(): bool
    {
        return $this->isUpdateRequest();
    }
}
