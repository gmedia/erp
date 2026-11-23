<?php

namespace App\Actions\CreditNotes;

use App\Actions\AccountingPosting\PostCreditNoteJournalAction;
use App\Models\CreditNote;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApplyCreditNoteAction
{
    public function __construct(
        private PostCreditNoteJournalAction $postJournal,
    ) {}

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

            $this->postJournal->execute($creditNote);

            $newCreditNoteAmount = (float) $invoice->credit_note_amount + (float) $creditNote->grand_total;
            $amountDue = (float) $invoice->grand_total
                - (float) $invoice->amount_received
                - $newCreditNoteAmount;
            $invoice->update([
                'credit_note_amount' => (string) $newCreditNoteAmount,
                'amount_due' => (string) $amountDue,
            ]);
            $invoice->updatePaymentStatus();

            return $creditNote->fresh();
        });
    }
}
