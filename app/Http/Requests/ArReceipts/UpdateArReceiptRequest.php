<?php

namespace App\Http\Requests\ArReceipts;

use Illuminate\Validation\Rule;

class UpdateArReceiptRequest extends AbstractArReceiptRequest
{
    protected function receiptNumberUniqueRule(): string|object
    {
        return Rule::unique('ar_receipts', 'receipt_number')->ignore($this->route('ar_receipt'));
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
