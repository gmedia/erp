<?php

namespace App\Http\Requests\ApPayments;

use Illuminate\Validation\Rule;

class UpdateApPaymentRequest extends AbstractApPaymentRequest
{
    protected function paymentNumberUniqueRule(): object
    {
        return Rule::unique('ap_payments', 'payment_number')->ignore($this->route('apPayment')?->id);
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
