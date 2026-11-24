<?php

namespace App\Http\Requests\ApPayments;

use App\Http\Requests\Concerns\ValidatesStatusTransition;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateApPaymentRequest extends AbstractApPaymentRequest
{
    use ValidatesStatusTransition;

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function ($validator): void {
            $this->validateResultStatusTransition($validator, $this->route('apPayment')?->status);
        });
    }

    protected function resultStatusSources(): array
    {
        return [
            'reconciled' => ['confirmed', 'reconciled'],
            'cancelled' => ['draft', 'confirmed'],
            'void' => ['draft', 'confirmed'],
        ];
    }

    protected function paymentNumberUniqueRule(): object
    {
        return Rule::unique('ap_payments', 'payment_number')->ignore($this->route('apPayment')?->id);
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
