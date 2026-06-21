<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class ComparativeReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'comparison_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id', 'different:fiscal_year_id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }
}
