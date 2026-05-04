<?php

namespace App\Exports;

use App\Actions\Reports\IndexApOutstandingReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Exports\Concerns\MapsSupplierBillExportRow;
use App\Http\Requests\Reports\IndexApOutstandingReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ApOutstandingReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    use MapsSupplierBillExportRow;

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
        return array_merge(
            $this->baseBillExportRow($row),
            [
                $row->days_overdue ?? 0,
            ],
            $this->billExportTrailingColumns($row)
        );
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
