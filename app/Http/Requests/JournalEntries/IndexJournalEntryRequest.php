<?php

namespace App\Http\Requests\JournalEntries;

use App\Http\Requests\BaseListingRequest;
use Illuminate\Validation\Rule;

class IndexJournalEntryRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'status' => ['nullable', Rule::in(['draft', 'posted', 'void'])],
            ],
            $this->listingSortRules('entry_date,entry_number,description,reference,total_debit,status,created_at'),
            $this->paginationRules(),
        );
    }
}
