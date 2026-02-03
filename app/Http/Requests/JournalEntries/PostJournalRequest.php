<?php

namespace App\Http\Requests\JournalEntries;

use Illuminate\Foundation\Http\FormRequest;

class PostJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:journal_entries,id'],
        ];
    }
}
