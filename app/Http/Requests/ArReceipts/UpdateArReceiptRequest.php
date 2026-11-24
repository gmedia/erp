<?php

namespace App\Http\Requests\ArReceipts;

use App\Http\Requests\Concerns\ValidatesStatusTransition;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateArReceiptRequest extends AbstractArReceiptRequest
{
    use ValidatesStatusTransition;

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function ($validator): void {
            $this->validateResultStatusTransition($validator, $this->route('ar_receipt')?->status);
        });
    }

    protected function resultStatusSources(): array
    {
        return ['reconciled' => ['confirmed', 'reconciled']];
    }

    protected function receiptNumberUniqueRule(): string|object
    {
        return Rule::unique('ar_receipts', 'receipt_number')->ignore($this->route('ar_receipt'));
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
