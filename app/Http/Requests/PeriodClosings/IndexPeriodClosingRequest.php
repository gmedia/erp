<?php

namespace App\Http\Requests\PeriodClosings;

use App\Http\Requests\BaseListingRequest;
use Illuminate\Validation\Rule;

class IndexPeriodClosingRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge($this->searchRules(), [
            'status' => ['nullable', Rule::in(['draft', 'closed', 'reopened'])],
            'closing_type' => ['nullable', Rule::in(['monthly', 'annual'])],
            'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
            'period_month' => ['nullable', 'integer', 'between:1,12'],
            'period_year' => ['nullable', 'integer', 'between:2000,2100'],
        ],
            $this->listingSortRules(
                'period_year,period_month,closing_type,status,net_income,'
                    . 'fiscal_year_id,closed_at,created_at',
            ),
            $this->paginationRules(),
        );
    }
}
