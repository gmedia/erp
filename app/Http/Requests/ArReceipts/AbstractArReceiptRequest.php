<?php

namespace App\Http\Requests\ArReceipts;

use App\Actions\ArReceipts\SyncArReceiptAllocationsAction;
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
                // POST tidak boleh membuat dokumen langsung berstatus hasil rekonsiliasi.
                // PUT boleh mengirim ulang status tersimpan atau mentransisikan confirmed -> reconciled.
                $this->usesSometimes()
                    ? 'in:draft,confirmed,reconciled,cancelled,void'
                    : 'in:draft,confirmed,cancelled,void',
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
            $receiptId = $this->route('ar_receipt')?->id;

            $this->validateAllocationCustomerMatch($validator);

            $this->validateAllocationOverflow(
                $validator,
                'allocations',
                'customer_invoice_id',
                __('ar_receipt.allocation_exceeds_invoice'),
                fn (int $invoiceId) => $this->maxInvoiceAllocation($validator, $invoiceId, $receiptId),
                // Issue #437: kas + diskon sama-sama memakan sisa invoice.
                fn (array $allocation) => (float) $allocation['allocated_amount']
                    + (float) ($allocation['discount_given'] ?? 0),
            );
        });
    }

    abstract protected function receiptNumberUniqueRule(): string|object;

    abstract protected function usesSometimes(): bool;

    protected function totalAmountRules(): array
    {
        return [
            'total_amount' => $this->withSometimes(['required', 'numeric', 'gt:0']),
        ];
    }

    private function validateAllocationCustomerMatch(Validator $validator): void
    {
        $customerId = (int) ($this->input('customer_id') ?? $this->route('ar_receipt')?->customer_id);

        if ($customerId === 0) {
            return;
        }

        foreach ($this->input('allocations', []) as $index => $allocation) {
            $invoiceId = $allocation['customer_invoice_id'] ?? null;

            if (empty($invoiceId)) {
                continue;
            }

            $belongsToCustomer = CustomerInvoice::whereKey($invoiceId)
                ->where('customer_id', $customerId)
                ->exists();

            if (! $belongsToCustomer) {
                $validator->errors()->add(
                    "allocations.{$index}.customer_invoice_id",
                    __('ar_receipt.invoice_customer_mismatch'),
                );
            }
        }
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
                __('ar_receipt.invoice_status_not_allocatable', ['status' => $invoice->status]),
            );

            return null;
        }

        // Only POSTED receipts consume the quota; drafts are re-checked on confirm.
        // Issue #437: kas + diskon sama-sama memakan sisa invoice.
        $existingAllocated = ArReceiptAllocation::where('customer_invoice_id', $invoice->id)
            ->whereHas(
                'receipt',
                fn ($q) => $q
                    ->whereIn('status', SyncArReceiptAllocationsAction::POSTED_STATUSES)
                    ->when($receiptId, fn ($q2) => $q2->where('id', '!=', $receiptId)),
            )
            ->selectRaw('COALESCE(SUM(allocated_amount + discount_given), 0) as settled')
            ->value('settled');

        return (float) $invoice->grand_total
            - (float) $existingAllocated
            - (float) $invoice->credit_note_amount;
    }
}
