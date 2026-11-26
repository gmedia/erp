<?php

namespace App\Exports;

use App\Actions\Reports\IndexArAgingReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Exports\Concerns\MapsCustomerInvoiceExportRow;
use App\Http\Requests\Reports\IndexArAgingReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ArAgingReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    use MapsCustomerInvoiceExportRow;

    public function headings(): array
    {
        return array_merge($this->getBaseInvoiceHeadings(), [
            'Current',
            '1-30 Days',
            '31-60 Days',
            '61-90 Days',
            'Over 90 Days',
        ]);
    }

    public function map($row): array
    {
        return array_merge($this->mapBaseInvoiceColumns($row), [
            $this->numericValue($row->aging_buckets['current'] ?? null) ?? 0,
            $this->numericValue($row->aging_buckets['1_30'] ?? null) ?? 0,
            $this->numericValue($row->aging_buckets['31_60'] ?? null) ?? 0,
            $this->numericValue($row->aging_buckets['61_90'] ?? null) ?? 0,
            $this->numericValue($row->aging_buckets['over_90'] ?? null) ?? 0,
        ]);
    }

    protected function currencyColumnHeadings(): array
    {
        return [
            'Grand Total',
            'Amount Received',
            'Credit Note Amount',
            'Amount Due',
            'Current',
            '1-30 Days',
            '31-60 Days',
            '61-90 Days',
            'Over 90 Days',
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
