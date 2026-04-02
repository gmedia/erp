<?php

namespace App\Exports;

use App\Actions\Reports\IndexGoodsReceiptReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexGoodsReceiptReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GoodsReceiptReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'GR Number',
            'Receipt Date',
            'Status',
            'PO Number',
            'Supplier',
            'Warehouse',
            'Item Count',
            'Total Received Qty',
            'Total Accepted Qty',
            'Total Rejected Qty',
            'Total Receipt Value',
        ];
    }

    public function map($row): array
    {
        return [
            $row->gr_number ?? '-',
            $row->receipt_date?->format('Y-m-d') ?? '-',
            $row->status ?? '-',
            $row->po_number ?? '-',
            $row->supplier_name ?? '-',
            $row->warehouse_name ?? '-',
            $row->item_count ?? 0,
            $row->total_received_quantity ?? 0,
            $row->total_accepted_quantity ?? 0,
            $row->total_rejected_quantity ?? 0,
            $row->total_receipt_value ?? 0,
        ];
    }

    protected function actionClass(): string
    {
        return IndexGoodsReceiptReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexGoodsReceiptReportRequest::class;
    }
}
