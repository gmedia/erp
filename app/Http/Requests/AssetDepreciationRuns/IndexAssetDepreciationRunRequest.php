<?php

namespace App\Http\Requests\AssetDepreciationRuns;

use App\Http\Requests\BaseListingRequest;
use Illuminate\Validation\Rule;

class IndexAssetDepreciationRunRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'status' => ['nullable', Rule::in(['draft', 'calculated', 'posted', 'void'])],
            ],
            $this->listingSortRules('period_start,period_end,status,created_at'),
            $this->paginationRules(),
        );
    }
}
