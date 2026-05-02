<?php

namespace App\Exports;

use App\Actions\Reports\IndexApOutstandingReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexApOutstandingReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ApOutstandingReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Bill Number',
            'Supplier Invoice',
            'Supplier',
            'Branch',
            'Bill Date',
            'Due Date',
            'Grand Total',
            'Amount Paid',
            'Amount Due',
            'Days Overdue',
            'Status',
            'Currency',
            'Payment Terms',
            'PO Number',
            'GR Number',
            'Notes',
        ];
    }

    public function map($row): array
    {
        return [
            $row->bill_number ?? '-',
            $row->supplier_invoice_number ?? '-',
            $row->supplier_name ?? '-',
            $row->branch_name ?? '-',
            $row->bill_date?->format('Y-m-d') ?? '-',
            $row->due_date?->format('Y-m-d') ?? '-',
            $row->grand_total ?? 0,
            $row->amount_paid ?? 0,
            $row->amount_due ?? 0,
            $row->days_overdue ?? 0,
            $row->status ?? '-',
            $row->currency ?? '-',
            $row->payment_terms ?? '-',
            $row->purchase_order_number ?? '-',
            $row->goods_receipt_number ?? '-',
            $row->notes ?? '-',
        ];
    }

    protected function actionClass(): string
    {
        return IndexApOutstandingReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexApOutstandingReportRequest::class;
    }
}
