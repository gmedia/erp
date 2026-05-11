<?php

namespace App\Http\Requests\RecurringJournals;

use App\Http\Requests\BaseListingRequest;
use Illuminate\Validation\Rule;

class IndexRecurringJournalRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'frequency' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'annual'])],
                'is_active' => ['nullable', 'boolean'],
                'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
                'next_run_from' => ['nullable', 'date'],
                'next_run_to' => ['nullable', 'date', 'after_or_equal:next_run_from'],
            ],
            $this->listingSortRules('name,frequency,next_run_date,last_run_date,total_amount,is_active,created_at'),
            $this->paginationRules(),
        );
    }
}
