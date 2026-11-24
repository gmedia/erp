<?php

namespace App\Http\Requests\PurchaseOrders;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;
use App\Http\Requests\Concerns\HasSupportedCurrencyRules;
use App\Http\Requests\Concerns\HasTransactionAmountRules;
use App\Models\PurchaseOrder;
use Illuminate\Validation\Validator;

abstract class AbstractPurchaseOrderRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;
    use HasSupportedCurrencyRules;
    use HasTransactionAmountRules;

    /**
     * Field yang memengaruhi nilai/penerima PO. Terkunci setelah status tidak lagi draft.
     */
    private const LOCKED_FIELDS_AFTER_DRAFT = ['items', 'supplier_id', 'warehouse_id', 'currency'];

    /**
     * Whitelist: status yang boleh diisi manual lewat create/update hanya draft (create)
     * atau status yang tidak berubah (update). Status pending_approval, confirmed, rejected,
     * partially_received, fully_received, closed hanya dihasilkan pipeline/approval atau GR.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->has('status')) {
                    return;
                }

                if (! $this->isAllowedManualStatus($this->input('status'))) {
                    $validator->errors()->add('status', __('approval.status_manual_forbidden'));
                }
            },
            function (Validator $validator): void {
                // Item dan header bernilai terkunci sejak pending_approval/confirmed; hanya saat draft boleh diubah.
                if ($this->currentPurchaseOrderStatus() === PurchaseOrder::STATUS_DRAFT) {
                    return;
                }

                $purchaseOrder = $this->route('purchaseOrder');

                if (! $purchaseOrder instanceof PurchaseOrder) {
                    return;
                }

                foreach (self::LOCKED_FIELDS_AFTER_DRAFT as $field) {
                    if (! $this->has($field) || $this->valueMatchesStored($purchaseOrder, $field)) {
                        continue;
                    }

                    $validator->errors()->add($field, __('approval.items_locked'));
                }
            },
        ];
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
            'currency' => $this->withSometimes(['required', ...$this->supportedCurrencyRules()]),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,pending_approval,confirmed,rejected,partially_received,fully_received,cancelled,closed',
            ]),
            'notes' => $this->withSometimes(['nullable', 'string']),
            'shipping_address' => $this->withSometimes(['nullable', 'string']),
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

    protected function isAllowedManualStatus(mixed $status): bool
    {
        // Create: hanya draft. Update: status tidak berubah.
        return $status === $this->currentPurchaseOrderStatus();
    }

    protected function currentPurchaseOrderStatus(): string
    {
        $purchaseOrder = $this->route('purchaseOrder');

        return $purchaseOrder instanceof PurchaseOrder ? $purchaseOrder->status : PurchaseOrder::STATUS_DRAFT;
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

    /**
     * Kiriman yang sama dengan nilai tersimpan tidak dianggap perubahan (form mengirim ulang semua field).
     */
    private function valueMatchesStored(PurchaseOrder $purchaseOrder, string $field): bool
    {
        if ($field === 'items') {
            return false;
        }

        return (string) $this->input($field) === (string) $purchaseOrder->getAttribute($field);
    }
}
