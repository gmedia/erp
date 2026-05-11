<?php

namespace App\Http\Requests\CustomerInvoices;

use Illuminate\Validation\Rule;

class UpdateCustomerInvoiceRequest extends AbstractCustomerInvoiceRequest
{
    protected function invoiceNumberUniqueRule(): string|object
    {
        return Rule::unique('customer_invoices', 'invoice_number')->ignore($this->route('customer_invoice'));
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
