<?php

namespace App\Http\Requests\CreditNotes;

use Illuminate\Validation\Rule;

class UpdateCreditNoteRequest extends AbstractCreditNoteRequest
{
    protected function creditNoteNumberUniqueRule(): string|object
    {
        return Rule::unique('credit_notes', 'credit_note_number')->ignore($this->route('credit_note'));
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
