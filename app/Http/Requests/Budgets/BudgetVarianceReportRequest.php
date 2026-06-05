<?php

namespace App\Http\Requests\Budgets;

use App\Http\Requests\AuthorizedFormRequest;

class BudgetVarianceReportRequest extends AuthorizedFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'budget_id' => ['required', 'integer', 'exists:budgets,id'],
            'status' => ['nullable', 'string', 'in:within_budget,warning,over_budget'],
            'account_type' => ['nullable', 'string', 'in:asset,liability,equity,revenue,expense'],
        ];
    }
}
