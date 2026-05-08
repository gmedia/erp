<?php

namespace App\Http\Requests\ApPayments;

class IndexApPaymentRequest extends AbstractApPaymentListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->apPaymentListingRules('supplier_id', 'branch_id'),
            [
                'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
                'bank_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
                'total_amount_min' => ['nullable', 'numeric', 'min:0'],
                'total_amount_max' => ['nullable', 'numeric', 'min:0'],
            ],
            $this->listingSortRules(
                'id,payment_number,supplier,supplier_id,branch,branch_id,payment_date,' .
                    'payment_method,currency,status,total_amount,total_allocated,total_unallocated,created_at,updated_at'
            ),
            $this->paginationRules(),
        );
    }
}
