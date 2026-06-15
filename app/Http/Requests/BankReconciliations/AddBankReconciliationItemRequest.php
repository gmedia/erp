<?php

namespace App\Http\Requests\BankReconciliations;

use Illuminate\Foundation\Http\FormRequest;

class AddBankReconciliationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'journal_entry_line_id' => ['nullable', 'integer', 'exists:journal_entry_lines,id'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'debit' => ['nullable', 'numeric', 'min:0'],
            'credit' => ['nullable', 'numeric', 'min:0'],
            'type' => ['nullable', 'string', 'max:30'],
            'is_reconciled' => ['boolean'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
