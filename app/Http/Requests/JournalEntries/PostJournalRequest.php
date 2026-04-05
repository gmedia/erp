<?php

namespace App\Http\Requests\JournalEntries;

use App\Http\Requests\AuthorizedFormRequest;

class PostJournalRequest extends AuthorizedFormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:journal_entries,id'],
        ];
    }
}
