<?php

namespace App\Http\Requests\ApPayments;

class ExportApPaymentRequest extends AbstractApPaymentListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->apPaymentListingRules('supplier', 'branch'),
            $this->listingSortRules('payment_number,payment_date,payment_method,currency,status,total_amount,total_allocated,created_at'),
        );
    }
}
