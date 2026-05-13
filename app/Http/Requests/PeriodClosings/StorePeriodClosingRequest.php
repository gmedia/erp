<?php

namespace App\Http\Requests\PeriodClosings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeriodClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'period_month' => ['nullable', 'integer', 'between:1,12', 'required_if:closing_type,monthly'],
            'period_year' => ['required', 'integer', 'between:2000,2100'],
            'closing_type' => ['required', Rule::in(['monthly', 'annual'])],
            'retained_earnings_account_id' => ['nullable', 'integer', 'exists:accounts,id', 'required_if:closing_type,annual'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
