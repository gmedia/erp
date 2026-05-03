<?php

namespace App\Http\Requests\SupplierBills;

use Illuminate\Validation\Rule;

class UpdateSupplierBillRequest extends AbstractSupplierBillRequest
{
    protected function billNumberUniqueRule(): object
    {
        return Rule::unique('supplier_bills', 'bill_number')->ignore($this->route('supplierBill')?->id);
    }

    protected function usesSometimes(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function totalAmountRules(): array
    {
        return $this->transactionAmountRules(['amount_paid', 'amount_due']);
    }
}
