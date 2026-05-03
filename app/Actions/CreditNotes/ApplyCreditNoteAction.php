<?php

namespace App\Actions\CreditNotes;

use App\Models\CreditNote;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApplyCreditNoteAction
{
    public function execute(CreditNote $creditNote): CreditNote
    {
        if ($creditNote->status !== 'confirmed') {
            throw new InvalidArgumentException('Only confirmed credit notes can be applied.');
        }

        $invoice = $creditNote->customerInvoice;
        if (! $invoice) {
            throw new InvalidArgumentException('Credit note must be linked to an invoice.');
        }

        if ((float) $creditNote->grand_total > (float) $invoice->amount_due) {
            throw new InvalidArgumentException('Credit note amount exceeds invoice outstanding.');
        }

        return DB::transaction(function () use ($creditNote, $invoice) {
            $creditNote->update(['status' => 'applied']);

            $newCreditNoteAmount = (float) $invoice->credit_note_amount + (float) $creditNote->grand_total;
            $invoice->update([
                'credit_note_amount' => (string) $newCreditNoteAmount,
                'amount_due' => (string) ((float) $invoice->grand_total - (float) $invoice->amount_received - $newCreditNoteAmount),
            ]);
            $invoice->updatePaymentStatus();

            return $creditNote->fresh();
        });
    }
}
