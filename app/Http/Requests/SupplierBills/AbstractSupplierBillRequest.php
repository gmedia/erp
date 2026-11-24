<?php

namespace App\Http\Requests\SupplierBills;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasInvoiceLikeRules;
use App\Http\Requests\Concerns\HasTransactionAmountRules;

abstract class AbstractSupplierBillRequest extends AuthorizedFormRequest
{
    use HasInvoiceLikeRules;
    use HasTransactionAmountRules;

    public function rules(): array
    {
        return [
            'bill_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->billNumberUniqueRule(),
            ]),
            'supplier_id' => $this->withSometimes(['required', 'integer', 'exists:suppliers,id']),
            'purchase_order_id' => $this->withSometimes(['nullable', 'integer', 'exists:purchase_orders,id']),
            'goods_receipt_id' => $this->withSometimes(['nullable', 'integer', 'exists:goods_receipts,id']),
            'supplier_invoice_number' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'supplier_invoice_date' => $this->withSometimes(['nullable', 'date']),
            'bill_date' => $this->withSometimes(['required', 'date']),
            'due_date' => $this->withSometimes($this->dueDateRules()),
            ...$this->invoiceLikeHeaderRules(),
            'status' => $this->withSometimes([
                'required',
                'string',
                // POST tidak boleh membuat bill langsung berstatus hasil pembayaran.
                // PUT boleh mengirim ulang status tersimpan atau mentransisikan confirmed -> overdue.
                $this->usesSometimes()
                    ? 'in:draft,confirmed,overdue,partially_paid,paid,cancelled,void'
                    : 'in:draft,confirmed,cancelled,void',
            ]),
            ...$this->totalAmountRules(),

            ...$this->invoiceLikeItemRules(),
            'items.*.goods_receipt_item_id' => ['nullable', 'integer', 'exists:goods_receipt_items,id'],
        ];
    }

    abstract protected function billNumberUniqueRule(): string|object;

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
    protected function dueDateRules(): array
    {
        return ['required', 'date'];
    }
}
