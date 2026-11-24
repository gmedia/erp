<?php

namespace App\Http\Requests\ApPayments;

use App\Http\Requests\Concerns\LocksPostedDocumentValues;
use App\Http\Requests\Concerns\ValidatesStatusTransition;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateApPaymentRequest extends AbstractApPaymentRequest
{
    use LocksPostedDocumentValues;
    use ValidatesStatusTransition;

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function ($validator): void {
            $document = $this->route('apPayment');
            $this->validateResultStatusTransition($validator, $document?->status);
            $this->validatePostedDocumentLock($validator, $document);
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
