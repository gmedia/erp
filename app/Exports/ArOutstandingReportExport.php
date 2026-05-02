<?php

namespace App\Exports;

use App\Actions\Reports\IndexArOutstandingReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexArOutstandingReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ArOutstandingReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Invoice Number',
            'Invoice Date',
            'Due Date',
            'Customer',
            'Branch',
            'Grand Total',
            'Amount Received',
            'Credit Note Amount',
            'Amount Due',
            'Status',
            'Days Overdue',
        ];
    }

    public function map($row): array
    {
        return [
            $row->customer_invoice['invoice_number'] ?? '-',
            $row->customer_invoice['invoice_date'] ?? '-',
            $row->customer_invoice['due_date'] ?? '-',
            $row->customer['name'] ?? '-',
            $row->branch['name'] ?? '-',
            $row->amounts['grand_total'] ?? 0,
            $row->amounts['amount_received'] ?? 0,
            $row->amounts['credit_note_amount'] ?? 0,
            $row->amounts['amount_due'] ?? 0,
            $row->customer_invoice['status'] ?? '-',
            $row->days_overdue ?? 0,
        ];
    }

    protected function actionClass(): string
    {
        return IndexArOutstandingReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexArOutstandingReportRequest::class;
    }
}