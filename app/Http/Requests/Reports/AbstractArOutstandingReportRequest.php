<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

abstract class AbstractArOutstandingReportRequest extends AbstractReportRequest
{
    protected function arOutstandingRules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
                'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
                'status' => ['nullable', 'string', Rule::in(['sent', 'partially_paid', 'overdue'])],
            ],
            $this->dateRangeRules(),
            $this->sortByEnumRules([
                'invoice_number',
                'customer_invoice_invoice_number',
                'invoice_date',
                'customer_invoice_invoice_date',
                'due_date',
                'customer_invoice_due_date',
                'status',
                'customer_invoice_status',
                'customer_name',
                'branch_name',
                'grand_total',
                'amount_received',
                'credit_note_amount',
                'amount_due',
                'days_overdue',
            ]),
            $this->sortDirectionRules(),
        );
    }
}