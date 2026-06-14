<?php

namespace App\Http\Requests\ArReceipts;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasBankPaymentRules;
use App\Http\Requests\Concerns\HasSometimesArrayRules;
use App\Http\Requests\Concerns\ValidatesAllocationOverflow;
use App\Models\ArReceiptAllocation;
use App\Models\CustomerInvoice;
use Illuminate\Contracts\Validation\Validator;

abstract class AbstractArReceiptRequest extends AuthorizedFormRequest
{
    use HasBankPaymentRules;
    use HasSometimesArrayRules;
    use ValidatesAllocationOverflow;

    public function rules(): array
    {
        return [
            'receipt_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->receiptNumberUniqueRule(),
            ]),
            'customer_id' => $this->withSometimes(['required', 'integer', 'exists:customers,id']),
            ...$this->bankPaymentSharedRules(
                'receipt_date',
                ['bank_transfer', 'cash', 'check', 'giro', 'credit_card', 'other'],
            ),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,confirmed,reconciled,cancelled,void',
            ]),
            ...$this->totalAmountRules(),

            'allocations' => $this->itemsRules(),
            'allocations.*.customer_invoice_id' => $this->requiredIntegerItemRule('exists:customer_invoices,id'),
            'allocations.*.allocated_amount' => $this->requiredNumericItemRule('gt:0'),
            'allocations.*.discount_given' => ['nullable', 'numeric', 'min:0'],
            'allocations.*.notes' => $this->nullableStringItemRule(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $receiptId = $this->route('arReceipt')?->id;

            $this->validateAllocationOverflow(
                $validator,
                'allocations',
                'customer_invoice_id',
                'Allocation exceeds invoice outstanding amount. Maximum',
                fn (int $invoiceId) => $this->maxInvoiceAllocation($validator, $invoiceId, $receiptId),
            );
        });
    }

    abstract protected function receiptNumberUniqueRule(): string|object;

    abstract protected function usesSometimes(): bool;

    protected function totalAmountRules(): array
    {
        return [];
    }

    private function maxInvoiceAllocation(
        Validator $validator,
        int $invoiceId,
        ?int $receiptId,
    ): ?float {
        $invoice = CustomerInvoice::find($invoiceId);

        if ($invoice === null) {
            return null;
        }

        if (in_array($invoice->status, ['void', 'cancelled'], true)) {
            $validator->errors()->add(
                'allocations.customer_invoice_id',
                "Cannot allocate to a {$invoice->status} invoice.",
            );

            return null;
        }

        $existingAllocated = ArReceiptAllocation::where('customer_invoice_id', $invoice->id)
            ->when(
                $receiptId,
                fn ($q) => $q->whereHas(
                    'receipt',
                    fn ($q2) => $q2->where('id', '!=', $receiptId),
                ),
            )
            ->sum('allocated_amount');

        return (float) $invoice->grand_total
            - (float) $existingAllocated
            - (float) $invoice->credit_note_amount;
    }
}
