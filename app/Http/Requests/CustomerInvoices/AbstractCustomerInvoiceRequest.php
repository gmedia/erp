<?php

namespace App\Http\Requests\CustomerInvoices;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasInvoiceLikeRules;

abstract class AbstractCustomerInvoiceRequest extends AuthorizedFormRequest
{
    use HasInvoiceLikeRules;

    public function rules(): array
    {
        return [
            'invoice_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->invoiceNumberUniqueRule(),
            ]),
            'customer_id' => $this->withSometimes(['required', 'integer', 'exists:customers,id']),
            'invoice_date' => $this->withSometimes(['required', 'date']),
            'due_date' => $this->withSometimes(['required', 'date']),
            ...$this->invoiceLikeHeaderRules(),
            'status' => $this->withSometimes([
                'required',
                'string',
                // POST tidak boleh membuat invoice langsung berstatus hasil pembayaran.
                // PUT boleh mengirim ulang status tersimpan atau mentransisikan sent -> overdue.
                $this->usesSometimes()
                    ? 'in:draft,sent,overdue,partially_paid,paid,cancelled,void'
                    : 'in:draft,sent,cancelled,void',
            ]),
            ...$this->totalAmountRules(),

            ...$this->invoiceLikeItemRules(),
            'items.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ];
    }

    abstract protected function invoiceNumberUniqueRule(): string|object;

    abstract protected function usesSometimes(): bool;

    protected function totalAmountRules(): array
    {
        return [];
    }
}
