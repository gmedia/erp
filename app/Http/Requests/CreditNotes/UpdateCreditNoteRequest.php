<?php

namespace App\Http\Requests\CreditNotes;

use App\Http\Requests\Concerns\ValidatesStatusTransition;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateCreditNoteRequest extends AbstractCreditNoteRequest
{
    use ValidatesStatusTransition;

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $this->validateResultStatusTransition($validator, $this->route('creditNote')?->status);
        });
    }

    /** Hanya draft yang boleh dikonfirmasi; applied dicapai dari confirmed. */
    protected function resultStatusSources(): array
    {
        return [
            'confirmed' => ['draft'],
            'applied' => ['confirmed'],
            // applied sudah posting (jurnal + sisa invoice); batal lewat jurnal pembalik (#375).
            'cancelled' => ['draft', 'confirmed', 'applied'],
            'void' => ['draft', 'confirmed', 'applied'],
        ];
    }

    protected function creditNoteNumberUniqueRule(): string|object
    {
        return Rule::unique('credit_notes', 'credit_note_number')->ignore($this->route('credit_note'));
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
