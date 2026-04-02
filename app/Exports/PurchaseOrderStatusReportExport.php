<?php

namespace App\Exports;

use App\Actions\Reports\IndexPurchaseOrderStatusReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexPurchaseOrderStatusReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseOrderStatusReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'PO Number',
            'Supplier',
            'Warehouse',
            'Order Date',
            'Expected Delivery',
            'Status',
            'Status Category',
            'Item Count',
            'Ordered Quantity',
            'Received Quantity',
            'Outstanding Quantity',
            'Receipt Progress (%)',
            'Grand Total',
        ];
    }

    public function map($row): array
    {
        return [
            $row->po_number ?? '-',
            $row->supplier_name ?? '-',
            $row->warehouse_name ?? '-',
            $row->order_date?->format('Y-m-d') ?? '-',
            $row->expected_delivery_date?->format('Y-m-d') ?? '-',
            $row->status ?? '-',
            $row->status_category ?? '-',
            $row->item_count ?? 0,
            $row->ordered_quantity ?? 0,
            $row->received_quantity ?? 0,
            $row->outstanding_quantity ?? 0,
            $row->receipt_progress_percent ?? 0,
            $row->grand_total ?? 0,
        ];
    }

    protected function actionClass(): string
    {
        return IndexPurchaseOrderStatusReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexPurchaseOrderStatusReportRequest::class;
    }
}
