<?php

namespace App\Http\Requests\CreditNotes;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractCreditNoteListingRequest extends BaseListingRequest
{
    protected function creditNoteListingRules(string $customerKey, string $branchKey): array
    {
        return [
            'search' => ['nullable', 'string'],
            $customerKey => ['nullable', 'integer', 'exists:customers,id'],
            $branchKey => ['nullable', 'integer', 'exists:branches,id'],
            'reason' => [
                'nullable',
                'string',
                'in:return,discount,correction,bad_debt,other',
            ],
            'status' => [
                'nullable',
                'string',
                'in:draft,confirmed,applied,cancelled,void',
            ],
            'credit_note_date_from' => ['nullable', 'date'],
            'credit_note_date_to' => ['nullable', 'date', 'after_or_equal:credit_note_date_from'],
        ];
    }
}
