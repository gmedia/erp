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
            $this->numericValue($row->ordered_quantity) ?? 0,
            $this->numericValue($row->received_quantity) ?? 0,
            $this->numericValue($row->outstanding_quantity) ?? 0,
            $this->numericValue($row->receipt_progress_percent) ?? 0,
            $this->numericValue($row->grand_total) ?? 0,
        ];
    }

    protected function numericColumnHeadings(): array
    {
        return [
            'Item Count',
            'Ordered Quantity',
            'Received Quantity',
            'Outstanding Quantity',
            'Receipt Progress (%)',
        ];
    }

    protected function currencyColumnHeadings(): array
    {
        return ['Grand Total'];
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
