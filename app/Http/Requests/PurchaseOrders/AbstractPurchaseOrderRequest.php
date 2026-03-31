<?php

namespace App\Http\Requests\PurchaseOrders;

use App\Http\Requests\Concerns\HasSometimesArrayRules;
use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractPurchaseOrderRequest extends FormRequest
{
    use HasSometimesArrayRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->poNumberUniqueRule(),
            ]),
            'supplier_id' => $this->withSometimes(['required', 'integer', 'exists:suppliers,id']),
            'warehouse_id' => $this->withSometimes(['required', 'integer', 'exists:warehouses,id']),
            'order_date' => $this->withSometimes(['required', 'date']),
            'expected_delivery_date' => $this->withSometimes($this->expectedDeliveryDateRules()),
            'payment_terms' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'currency' => $this->withSometimes(['required', 'string', 'max:3']),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,pending_approval,confirmed,rejected,partially_received,fully_received,cancelled,closed',
            ]),
            'notes' => $this->withSometimes(['nullable', 'string']),
            'shipping_address' => $this->withSometimes(['nullable', 'string']),
            'approved_by' => $this->withSometimes(['nullable', 'integer', 'exists:users,id']),
            'approved_at' => $this->withSometimes(['nullable', 'date']),
            ...$this->totalAmountRules(),

            'items' => $this->itemsRules(),
            'items.*.purchase_request_item_id' => ['nullable', 'integer', 'exists:purchase_request_items,id'],
            'items.*.product_id' => [$this->itemRequiredRule(), 'integer', 'exists:products,id'],
            'items.*.unit_id' => [$this->itemRequiredRule(), 'integer', 'exists:units,id'],
            'items.*.quantity' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'items.*.unit_price' => [$this->itemRequiredRule(), 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    abstract protected function poNumberUniqueRule(): string|object;

    abstract protected function usesSometimes(): bool;

    /**
     * @return array<string, array<int, string>>
     */
    protected function totalAmountRules(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function expectedDeliveryDateRules(): array
    {
        return ['nullable', 'date'];
    }
}
