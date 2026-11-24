<?php

namespace App\Http\Requests\SupplierBills;

use App\Http\Requests\Concerns\ValidatesStatusTransition;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateSupplierBillRequest extends AbstractSupplierBillRequest
{
    use ValidatesStatusTransition;

    /** Stored statuses from which a PUT may set overdue. Drafts must be confirmed first. */
    private const OVERDUE_FROM = ['confirmed', 'partially_paid', 'overdue'];

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $this->validateResultStatusTransition(
                $validator,
                $this->route('supplierBill')?->status,
            );
        });
    }

    /**
     * partially_paid dan paid hanya dikirim ulang bila sudah tersimpan (sumber kosong).
     * overdue mengikuti aturan lama: draft harus dikonfirmasi dulu.
     */
    protected function resultStatusSources(): array
    {
        return [
            'partially_paid' => [],
            'paid' => [],
            'overdue' => self::OVERDUE_FROM,
        ];
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
