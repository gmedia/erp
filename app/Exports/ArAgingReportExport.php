<?php

namespace App\Exports;

use App\Actions\Reports\IndexArAgingReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexArAgingReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ArAgingReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
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
            'Current',
            '1-30 Days',
            '31-60 Days',
            '61-90 Days',
            'Over 90 Days',
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
            $row->aging_buckets['current'] ?? 0,
            $row->aging_buckets['1_30'] ?? 0,
            $row->aging_buckets['31_60'] ?? 0,
            $row->aging_buckets['61_90'] ?? 0,
            $row->aging_buckets['over_90'] ?? 0,
        ];
    }

    protected function actionClass(): string
    {
        return IndexArAgingReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexArAgingReportRequest::class;
    }
}