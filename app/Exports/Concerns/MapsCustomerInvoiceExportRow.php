<?php

namespace App\Exports\Concerns;

trait MapsCustomerInvoiceExportRow
{
    protected function mapBaseInvoiceColumns($row): array
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
