<?php

namespace App\Http\Requests\CustomerInvoices;

class ExportCustomerInvoiceRequest extends AbstractCustomerInvoiceListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->customerInvoiceListingRules('customer_id', 'branch_id'),
            [
                'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
                'grand_total_min' => ['nullable', 'numeric', 'min:0'],
                'grand_total_max' => ['nullable', 'numeric', 'min:0'],
                'amount_due_min' => ['nullable', 'numeric', 'min:0'],
                'amount_due_max' => ['nullable', 'numeric', 'min:0'],
            ],
            $this->listingSortRules(
                'id,invoice_number,customer,customer_id,branch,branch_id,invoice_date,due_date,' .
                    'currency,status,grand_total,amount_due,created_at,updated_at'
            ),
        );
    }
}
