<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

abstract class AbstractApOutstandingReportRequest extends AbstractReportRequest
{
    protected function apOutstandingRules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
                'status' => [
                    'nullable',
                    'string',
                    Rule::in([
                        'confirmed',
                        'partially_paid',
                        'overdue',
                    ]),
                ],
                'due_date_from' => ['nullable', 'date'],
                'due_date_to' => ['nullable', 'date', 'after_or_equal:due_date_from'],
            ],
            $this->sortByEnumRules([
                'supplier_name',
                'bill_number',
                'bill_date',
                'due_date',
                'grand_total',
                'amount_paid',
                'amount_due',
                'days_overdue',
                'status',
            ]),
            $this->sortDirectionRules(),
        );
    }
}
