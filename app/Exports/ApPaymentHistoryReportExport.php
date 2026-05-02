<?php

namespace App\Exports;

use App\Actions\Reports\IndexApPaymentHistoryReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexApPaymentHistoryReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ApPaymentHistoryReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Payment Number',
            'Payment Date',
            'Supplier',
            'Branch',
            'Payment Method',
            'Bank Account',
            'Account Number',
            'Total Amount',
            'Allocated Amount',
            'Unallocated Amount',
            'Reference',
            'Status',
            'Currency',
            'Notes',
        ];
    }

    public function map($row): array
    {
        return [
            $row->payment_number ?? '-',
            $row->payment_date?->format('Y-m-d') ?? '-',
            $row->supplier_name ?? '-',
            $row->branch_name ?? '-',
            $row->payment_method ?? '-',
            $row->bank_account_name ?? '-',
            $row->bank_account_number ?? '-',
            $row->total_amount ?? 0,
            $row->total_allocated ?? 0,
            $row->total_unallocated ?? 0,
            $row->reference ?? '-',
            $row->status ?? '-',
            $row->currency ?? '-',
            $row->notes ?? '-',
        ];
    }

    protected function actionClass(): string
    {
        return IndexApPaymentHistoryReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexApPaymentHistoryReportRequest::class;
    }
}
