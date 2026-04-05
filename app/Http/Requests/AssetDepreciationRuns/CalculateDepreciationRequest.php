<?php

namespace App\Http\Requests\AssetDepreciationRuns;

use App\Http\Requests\AuthorizedFormRequest;

class CalculateDepreciationRequest extends AuthorizedFormRequest
{
    public function rules(): array
    {
        return [
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ];
    }
}
