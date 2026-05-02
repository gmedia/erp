<?php

namespace App\Http\Requests\CustomerInvoices;

use Illuminate\Validation\Rule;

class StoreCustomerInvoiceRequest extends AbstractCustomerInvoiceRequest
{
    protected function invoiceNumberUniqueRule(): string|object
    {
        return Rule::unique('customer_invoices', 'invoice_number');
    }

    protected function usesSometimes(): bool
    {
        return false;
    }
}
