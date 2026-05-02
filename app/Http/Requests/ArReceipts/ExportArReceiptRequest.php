<?php

namespace App\Http\Requests\ArReceipts;

class ExportArReceiptRequest extends AbstractArReceiptListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->arReceiptListingRules('customer_id', 'branch_id'),
            [
                'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
                'bank_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
                'total_amount_min' => ['nullable', 'numeric', 'min:0'],
                'total_amount_max' => ['nullable', 'numeric', 'min:0'],
            ],
            $this->listingSortRules(
                'id,receipt_number,customer,customer_id,branch,branch_id,receipt_date,' .
                    'payment_method,currency,status,total_amount,created_at,updated_at'
            ),
        );
    }
}
