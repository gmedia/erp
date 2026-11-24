<?php

namespace App\Exports\Concerns;

trait MapsCustomerInvoiceExportRow
{
    protected function mapBaseInvoiceColumns($row): array
    {
        return [
            $row->invoice_number ?? '-',
            $row->invoice_date ?? '-',
            $row->due_date ?? '-',
            $row->customer_name ?? '-',
            $row->branch_name ?? '-',
            $row->grand_total ?? 0,
            $row->amount_received ?? 0,
            $row->credit_note_amount ?? 0,
            $row->amount_due ?? 0,
            $row->status ?? '-',
        ];
    }

    protected function getBaseInvoiceHeadings(): array
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
        ];
    }
}
