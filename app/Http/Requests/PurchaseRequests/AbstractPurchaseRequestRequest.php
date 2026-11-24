<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasRouteIgnoredUniqueRule;
use App\Http\Requests\Concerns\HasSometimesArrayRules;
use App\Models\PurchaseRequest;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

abstract class AbstractPurchaseRequestRequest extends AuthorizedFormRequest
{
    use HasRouteIgnoredUniqueRule;
    use HasSometimesArrayRules;

    /**
     * Whitelist: status yang boleh diisi manual lewat create/update.
     * Status lain (pending_approval, approved, rejected, partially_ordered, fully_ordered)
     * hanya dihasilkan oleh pipeline/approval atau sistem (konversi PO).
     * Dokumen yang sudah berstatus tertentu tetap boleh dikirim ulang dengan status yang sama.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->has('status')) {
                    return;
                }

                $status = $this->input('status');

                if (! $this->isAllowedManualStatus($status)) {
                    $validator->errors()->add('status', __('approval.status_manual_forbidden'));
                }
            },
            function (Validator $validator): void {
                // Item terkunci sejak pending_approval; update item hanya saat masih draft.
                if ($this->has('items') && $this->currentPurchaseRequestStatus() !== PurchaseRequest::STATUS_DRAFT) {
                    $validator->errors()->add('items', __('approval.items_locked'));
                }
            },
        ];
    }

    public function rules(): array
    {
        return [
            'pr_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->prNumberUniqueRule(),
            ]),
            'branch_id' => $this->withSometimes(['required', 'integer', 'exists:branches,id']),
            'department_id' => $this->withSometimes(['nullable', 'integer', 'exists:departments,id']),
            'requested_by' => $this->withSometimes(['nullable', 'integer', 'exists:employees,id']),
            'request_date' => $this->withSometimes(['required', 'date']),
            'required_date' => $this->withSometimes($this->requiredDateRules()),
            'priority' => $this->withSometimes(['required', 'string', 'in:low,normal,high,urgent']),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,pending_approval,approved,rejected,partially_ordered,fully_ordered,cancelled',
            ]),
            'estimated_amount' => $this->withSometimes(['nullable', 'numeric', 'min:0']),
            'notes' => $this->withSometimes(['nullable', 'string']),
            'rejection_reason' => $this->withSometimes(['nullable', 'string']),

            'items' => $this->itemsRules(),
            'items.*.product_id' => [$this->itemRequiredRule(), 'integer', 'exists:products,id'],
            'items.*.unit_id' => [$this->itemRequiredRule(), 'integer', 'exists:units,id'],
            'items.*.quantity' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'items.*.estimated_unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    protected function isAllowedManualStatus(mixed $status): bool
    {
        // Create: hanya draft. Update: status tidak berubah.
        return $status === $this->currentPurchaseRequestStatus();
    }

    protected function currentPurchaseRequestStatus(): string
    {
        $purchaseRequest = $this->route('purchaseRequest');

        return $purchaseRequest instanceof PurchaseRequest ? $purchaseRequest->status : PurchaseRequest::STATUS_DRAFT;
    }

    /**
     * @return array<int, string>
     */
    protected function requiredDateRules(): array
    {
        return ['nullable', 'date'];
    }

    protected function prNumberUniqueRule(): Unique
    {
        return $this->routeIgnoredUniqueRule('purchase_requests', 'pr_number', 'purchaseRequest');
    }

    abstract protected function usesSometimes(): bool;
}
