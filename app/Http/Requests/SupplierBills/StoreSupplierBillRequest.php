<?php

namespace App\Http\Requests\SupplierBills;

class StoreSupplierBillRequest extends AbstractSupplierBillRequest
{
    protected function billNumberUniqueRule(): string
    {
        return 'unique:supplier_bills,bill_number';
    }

    protected function usesSometimes(): bool
    {
        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function dueDateRules(): array
    {
        return ['required', 'date', 'after_or_equal:bill_date'];
    }
}
