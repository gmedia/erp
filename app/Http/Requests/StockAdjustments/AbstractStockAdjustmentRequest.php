<?php

namespace App\Http\Requests\StockAdjustments;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;
use App\Http\Requests\Concerns\ValidatesStatusTransition;
use App\Models\StockAdjustment;
use Illuminate\Contracts\Validation\Validator;

abstract class AbstractStockAdjustmentRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;
    use ValidatesStatusTransition;

    public function withValidator(Validator $validator): void
    {
        if (! $this->usesSometimes()) {
            return;
        }

        $validator->after(function ($validator): void {
            $document = $this->route('stock_adjustment') ?? $this->route('stockAdjustment');
            $this->validateResultStatusTransition($validator, $document?->status);
        });
    }

    public function rules(): array
    {
        return [
            'adjustment_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->adjustmentNumberUniqueRule(),
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

    /**
     * Stok dan jurnal hanya terbentuk saat approved. Pembatalan dari draft/pending_approval
     * tidak punya yang dibalik; pembatalan dari approved membalik stok dan jurnal bila ada
     * (data lama tanpa jurnal tetap bisa dibatalkan). pending_approval boleh kembali ke draft.
     */
    protected function resultStatusSources(): array
    {
        return [
            'draft' => ['pending_approval'],
            'approved' => ['pending_approval', 'draft'],
            'cancelled' => ['draft', 'pending_approval', 'approved'],
        ];
    }

    abstract protected function usesSometimes(): bool;

    private function adjustmentNumberUniqueRule(): string
    {
        if (! $this->usesSometimes()) {
            return 'unique:stock_adjustments,adjustment_number';
        }

        $adjustment = $this->route('stock_adjustment') ?? $this->route('stockAdjustment');
        $adjustmentId = $adjustment instanceof StockAdjustment ? $adjustment->getKey() : $adjustment;

        return 'unique:stock_adjustments,adjustment_number,' . $adjustmentId;
    }
}
