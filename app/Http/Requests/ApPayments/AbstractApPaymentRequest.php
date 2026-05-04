<?php

namespace App\Http\Requests\ApPayments;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;
use App\Models\ApPaymentAllocation;
use App\Models\SupplierBill;
use Illuminate\Contracts\Validation\Validator;

abstract class AbstractApPaymentRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;

    public function rules(): array
    {
        return [
            'payment_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->paymentNumberUniqueRule(),
            ]),
            'supplier_id' => $this->withSometimes(['required', 'integer', 'exists:suppliers,id']),
            'branch_id' => $this->withSometimes(['required', 'integer', 'exists:branches,id']),
            'fiscal_year_id' => $this->withSometimes(['required', 'integer', 'exists:fiscal_years,id']),
            'payment_date' => $this->withSometimes(['required', 'date']),
            'payment_method' => $this->withSometimes([
                'required',
                'string',
                'in:bank_transfer,cash,check,giro,other',
            ]),
            'bank_account_id' => $this->withSometimes(['required', 'integer', 'exists:accounts,id']),
            'currency' => $this->withSometimes(['required', 'string', 'max:3']),
            'total_amount' => $this->withSometimes(['required', 'numeric', 'gt:0']),
            'reference' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,pending_approval,confirmed,reconciled,cancelled,void',
            ]),
            'notes' => $this->withSometimes(['nullable', 'string']),

            'allocations' => $this->itemsRules(),
            'allocations.*.supplier_bill_id' => [$this->itemRequiredRule(), 'integer', 'exists:supplier_bills,id'],
            'allocations.*.allocated_amount' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'allocations.*.discount_taken' => ['nullable', 'numeric', 'min:0'],
            'allocations.*.notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $allocations = $this->input('allocations', []);
            $paymentId = $this->route('apPayment')?->id;

            foreach ($allocations as $index => $allocation) {
                if (empty($allocation['supplier_bill_id']) || empty($allocation['allocated_amount'])) {
                    continue;
                }

                $bill = SupplierBill::find($allocation['supplier_bill_id']);
                if ($bill === null) {
                    continue;
                }

                $existingAllocated = ApPaymentAllocation::where('supplier_bill_id', $bill->id)
                    ->when($paymentId, fn ($q) => $q->whereHas('payment', fn ($q2) => $q2->where('id', '!=', $paymentId)))
                    ->sum('allocated_amount');

                $newTotal = $existingAllocated + (float) $allocation['allocated_amount'];

                if ($newTotal > (float) $bill->grand_total) {
                    $validator->errors()->add(
                        "allocations.{$index}.allocated_amount",
                        'Allocation exceeds bill outstanding amount. Maximum: ' . ((float) $bill->grand_total - $existingAllocated)
                    );
                }
            }
        });
    }

    abstract protected function paymentNumberUniqueRule(): string|object;

    abstract protected function usesSometimes(): bool;
}
