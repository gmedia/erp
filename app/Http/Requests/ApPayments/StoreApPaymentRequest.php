<?php

namespace App\Http\Requests\ApPayments;

class StoreApPaymentRequest extends AbstractApPaymentRequest
{
    protected function paymentNumberUniqueRule(): string
    {
        return 'unique:ap_payments,payment_number';
    }

    protected function usesSometimes(): bool
    {
        return false;
    }
}
