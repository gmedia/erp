<?php

namespace App\Http\Requests\Budgets;

use App\Http\Requests\BaseListingRequest;
use Illuminate\Validation\Rule;

class IndexBudgetRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
                'budget_type' => ['nullable', Rule::in(['operational', 'capital', 'project', 'revenue'])],
                'status' => ['nullable', Rule::in(['draft', 'approved', 'locked'])],
            ],
            $this->listingSortRules(
                'name,budget_type,status,total_amount,created_at',
            ),
            $this->paginationRules(),
        );
    }
}
