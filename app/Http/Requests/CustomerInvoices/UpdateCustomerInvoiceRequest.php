<?php

namespace App\Http\Requests\CustomerInvoices;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateCustomerInvoiceRequest extends AbstractCustomerInvoiceRequest
{
    /** Stored statuses from which a PUT may set overdue. Drafts must be sent first. */
    private const OVERDUE_FROM = ['sent', 'partially_paid', 'overdue'];

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $target = $this->input('status');
            $current = $this->route('customer_invoice')?->status;

            if ($target === 'overdue' && $current !== null && ! in_array($current, self::OVERDUE_FROM, true)) {
                $validator->errors()->add('status', "Cannot change status from {$current} to overdue.");
            }
        });
    }

    protected function invoiceNumberUniqueRule(): string|object
    {
        return Rule::unique('customer_invoices', 'invoice_number')->ignore($this->route('customer_invoice'));
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
