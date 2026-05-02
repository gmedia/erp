<?php

namespace App\Exports;

use App\Actions\Reports\IndexCustomerStatementReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexCustomerStatementReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerStatementReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Invoice Number',
            'Invoice Date',
            'Due Date',
            'Grand Total',
            'Amount Received',
            'Credit Note Amount',
            'Amount Due',
            'Status',
            'Running Balance',
        ];
    }

    public function map($row): array
    {
        return [
            $row->customer_invoice['invoice_number'] ?? '-',
            $row->customer_invoice['invoice_date'] ?? '-',
            $row->customer_invoice['due_date'] ?? '-',
            $row->amounts['grand_total'] ?? 0,
            $row->amounts['amount_received'] ?? 0,
            $row->amounts['credit_note_amount'] ?? 0,
            $row->amounts['amount_due'] ?? 0,
            $row->customer_invoice['status'] ?? '-',
            $row->running_balance ?? 0,
        ];
    }

    protected function actionClass(): string
    {
        return IndexCustomerStatementReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexCustomerStatementReportRequest::class;
    }
}