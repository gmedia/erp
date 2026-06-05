<?php

namespace App\Http\Requests\Budgets;

use App\Http\Requests\AuthorizedFormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends AuthorizedFormRequest
{
    public function rules(): array
    {
        return [
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'budget_type' => ['required', Rule::in(['operational', 'capital', 'project', 'revenue'])],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.period_start' => ['required', 'date_format:Y-m-d'],
            'lines.*.period_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:lines.*.period_start'],
            'lines.*.allocated_amount' => ['required', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ];
    }
}
