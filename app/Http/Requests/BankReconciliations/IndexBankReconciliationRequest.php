<?php

namespace App\Http\Requests\BankReconciliations;

use App\Http\Requests\BaseListingRequest;
use Illuminate\Validation\Rule;

class IndexBankReconciliationRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'status' => ['nullable', Rule::in(['draft', 'in_progress', 'completed', 'cancelled'])],
                'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
                'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
                'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            ],
            $this->listingSortRules(
                'account_id,reconciliation_date,period_start,period_end,'
                    . 'statement_balance,book_balance,difference,status,created_at',
            ),
            $this->paginationRules(),
        );
    }
}
