<?php

namespace App\Http\Requests\CreditNotes;

use Illuminate\Validation\Rule;

class StoreCreditNoteRequest extends AbstractCreditNoteRequest
{
    protected function creditNoteNumberUniqueRule(): string|object
    {
        return Rule::unique('credit_notes', 'credit_note_number');
    }

    protected function usesSometimes(): bool
    {
        return false;
    }
}
