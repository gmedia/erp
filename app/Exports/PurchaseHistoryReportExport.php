<?php

namespace App\Exports;

use App\Actions\Reports\IndexPurchaseHistoryReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexPurchaseHistoryReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseHistoryReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'PO Number',
            'Order Date',
            'Expected Delivery',
            'PO Status',
            'Supplier',
            'Warehouse',
            'Product Code',
            'Product Name',
            'Ordered Quantity',
            'Received Quantity',
            'Outstanding Quantity',
            'Receipt Count',
            'Last Receipt Date',
            'Total Purchase Value',
        ];
    }

    public function map($row): array
    {
        return [
            $row->po_number ?? '-',
            $row->order_date?->format('Y-m-d') ?? '-',
            $row->expected_delivery_date?->format('Y-m-d') ?? '-',
            $row->status ?? '-',
            $row->supplier_name ?? '-',
            $row->warehouse_name ?? '-',
            $row->product_code ?? '-',
            $row->product_name ?? '-',
            $row->ordered_quantity ?? 0,
            $row->received_quantity ?? 0,
            $row->outstanding_quantity ?? 0,
            $row->receipt_count ?? 0,
            $row->last_receipt_date?->format('Y-m-d') ?? '-',
            $row->total_purchase_value ?? 0,
        ];
    }

    protected function actionClass(): string
    {
        return IndexPurchaseHistoryReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexPurchaseHistoryReportRequest::class;
    }
}
