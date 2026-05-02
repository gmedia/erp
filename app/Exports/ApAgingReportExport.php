<?php

namespace App\Exports;

use App\Actions\Reports\IndexApAgingReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexApAgingReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ApAgingReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
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
            'Status',
            'Currency',
            'Current',
            '1-30 Days',
            '31-60 Days',
            '61-90 Days',
            '>90 Days',
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
            $row->status ?? '-',
            $row->currency ?? '-',
            $row->current_amount ?? 0,
            $row->days_1_30 ?? 0,
            $row->days_31_60 ?? 0,
            $row->days_61_90 ?? 0,
            $row->days_over_90 ?? 0,
            $row->payment_terms ?? '-',
            $row->purchase_order_number ?? '-',
            $row->goods_receipt_number ?? '-',
            $row->notes ?? '-',
        ];
    }

    protected function actionClass(): string
    {
        return IndexApAgingReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexApAgingReportRequest::class;
    }
}
