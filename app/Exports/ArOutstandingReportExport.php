<?php

namespace App\Exports;

use App\Actions\Reports\IndexArOutstandingReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Exports\Concerns\ComputesDaysOverdue;
use App\Exports\Concerns\MapsCustomerInvoiceExportRow;
use App\Http\Requests\Reports\IndexArOutstandingReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ArOutstandingReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    use ComputesDaysOverdue;
    use MapsCustomerInvoiceExportRow;

    private const OVERDUE_STATUSES = ['sent', 'partially_paid', 'overdue'];

    public function headings(): array
    {
        return array_merge($this->getBaseInvoiceHeadings(), [
            'Days Overdue',
        ]);
    }

    public function map($row): array
    {
        return array_merge($this->mapBaseInvoiceColumns($row), [
            $this->computeDaysOverdue($row->due_date, $row->status, self::OVERDUE_STATUSES),
        ]);
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
