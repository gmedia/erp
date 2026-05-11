<?php

namespace App\Http\Requests\ArReceipts;

use Illuminate\Validation\Rule;

class StoreArReceiptRequest extends AbstractArReceiptRequest
{
    protected function receiptNumberUniqueRule(): string|object
    {
        return Rule::unique('ar_receipts', 'receipt_number');
    }

    protected function usesSometimes(): bool
    {
        return false;
    }
}
