<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

abstract class AbstractApPaymentHistoryReportRequest extends AbstractReportRequest
{
    protected function apPaymentHistoryRules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
                'payment_method' => [
                    'nullable',
                    'string',
                    Rule::in([
                        'bank_transfer',
                        'cash',
                        'check',
                        'giro',
                        'other',
                    ]),
                ],
                'status' => [
                    'nullable',
                    'string',
                    Rule::in([
                        'confirmed',
                        'reconciled',
                    ]),
                ],
                'payment_date_from' => ['nullable', 'date'],
                'payment_date_to' => ['nullable', 'date', 'after_or_equal:payment_date_from'],
            ],
            $this->sortByEnumRules([
                'payment_number',
                'supplier_name',
                'branch_name',
                'payment_date',
                'payment_method',
                'bank_account_name',
                'total_amount',
                'total_allocated',
                'total_unallocated',
                'status',
            ]),
            $this->sortDirectionRules(),
        );
    }
}
