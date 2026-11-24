<?php

namespace App\Http\Requests\SupplierBills;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateSupplierBillRequest extends AbstractSupplierBillRequest
{
    /** Stored statuses from which a PUT may set overdue. Drafts must be confirmed first. */
    private const OVERDUE_FROM = ['confirmed', 'partially_paid', 'overdue'];

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $target = $this->input('status');
            $current = $this->route('supplierBill')?->status;

            if ($target === 'overdue' && $current !== null && ! in_array($current, self::OVERDUE_FROM, true)) {
                $validator->errors()->add('status', "Cannot change status from {$current} to overdue.");
            }
        });
    }

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
