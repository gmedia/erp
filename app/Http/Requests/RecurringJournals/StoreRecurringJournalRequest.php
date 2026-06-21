<?php

namespace App\Http\Requests\RecurringJournals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRecurringJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'annual'])],
            'next_run_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:next_run_date'],
            'auto_post' => ['boolean'],
            'is_active' => ['boolean'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $lines = $this->input('lines', []);
            $debit = collect($lines)->sum(fn (array $line): float => (float) ($line['debit'] ?? 0));
            $credit = collect($lines)->sum(fn (array $line): float => (float) ($line['credit'] ?? 0));

            if (bccomp((string) $debit, (string) $credit, 2) !== 0) {
                $validator->errors()->add('lines', 'Total debit must equal total credit.');
            }
        }];
    }
}
