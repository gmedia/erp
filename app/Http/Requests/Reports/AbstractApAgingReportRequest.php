<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

abstract class AbstractApAgingReportRequest extends AbstractReportRequest
{
    protected function apAgingRules(): array
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
                'as_of_date' => ['nullable', 'date'],
            ],
            $this->sortByEnumRules([
                'supplier_name',
                'bill_number',
                'bill_date',
                'due_date',
                'grand_total',
                'amount_due',
                'current_amount',
                'days_1_30',
                'days_31_60',
                'days_61_90',
                'days_over_90',
            ]),
            $this->sortDirectionRules(),
        );
    }
}
