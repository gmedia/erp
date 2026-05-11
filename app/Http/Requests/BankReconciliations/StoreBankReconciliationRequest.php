<?php

namespace App\Http\Requests\BankReconciliations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'reconciliation_date' => ['required', 'date'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'statement_balance' => ['required', 'numeric'],
            'book_balance' => ['required', 'numeric'],
            'reconciled_balance' => ['nullable', 'numeric'],
            'difference' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::in(['draft', 'in_progress', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.journal_entry_line_id' => ['nullable', 'integer', 'exists:journal_entry_lines,id'],
            'items.*.transaction_date' => ['required_with:items', 'date'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.debit' => ['nullable', 'numeric', 'min:0'],
            'items.*.credit' => ['nullable', 'numeric', 'min:0'],
            'items.*.type' => ['nullable', 'string', 'max:30'],
            'items.*.is_reconciled' => ['boolean'],
            'items.*.reference' => ['nullable', 'string', 'max:255'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
