<?php

namespace App\Http\Requests\Reports;

abstract class AbstractCustomerStatementReportRequest extends AbstractReportRequest
{
    protected function customerStatementRules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
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
                'running_balance',
            ]),
            $this->sortDirectionRules(),
        );
    }
}
