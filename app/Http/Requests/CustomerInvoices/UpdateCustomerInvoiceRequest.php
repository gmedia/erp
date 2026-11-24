<?php

namespace App\Http\Requests\CustomerInvoices;

use App\Http\Requests\Concerns\LocksPostedDocumentValues;
use App\Http\Requests\Concerns\ValidatesStatusTransition;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateCustomerInvoiceRequest extends AbstractCustomerInvoiceRequest
{
    use LocksPostedDocumentValues;
    use ValidatesStatusTransition;

    /** Stored statuses from which a PUT may set overdue. Drafts must be sent first. */
    private const OVERDUE_FROM = ['sent', 'partially_paid', 'overdue'];

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $document = $this->route('customer_invoice');
            $this->validateResultStatusTransition($validator, $document?->status);
            $this->validatePostedDocumentLock($validator, $document);
        });
    }

    /**
     * partially_paid dan paid hanya dikirim ulang bila sudah tersimpan (sumber kosong).
     * overdue mengikuti aturan lama: draft harus dikirim dulu.
     */
    protected function resultStatusSources(): array
    {
        return [
            'sent' => ['draft'],
            'partially_paid' => [],
            'paid' => [],
            'overdue' => self::OVERDUE_FROM,
            // Issue #375 (review #426 butir 6): invoice overdue tanpa pembayaran bisa dibatalkan.
            'cancelled' => ['draft', 'sent', 'overdue'],
            'void' => ['draft', 'sent', 'overdue'],
        ];
    }

    protected function invoiceNumberUniqueRule(): string|object
    {
        return Rule::unique('customer_invoices', 'invoice_number')->ignore($this->route('customer_invoice'));
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
