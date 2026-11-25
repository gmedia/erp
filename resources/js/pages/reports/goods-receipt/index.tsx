'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    goodsReceiptReportColumns,
    type GoodsReceiptReportItem,
} from '@/components/reports/goods-receipt/Columns';
import { createGoodsReceiptReportFilterFields } from '@/components/reports/goods-receipt/Filters';

export default function GoodsReceiptReportPage() {
    return (
        <ReportDataTablePage<GoodsReceiptReportItem>
            title="Goods Receipt Report"
            titleKey="page.goods_receipt.title"
            descriptionKey="page.goods_receipt.description"
            breadcrumbs={createReportBreadcrumbs(
                'Goods Receipt',
                '/reports/goods-receipt',
            )}
            columns={goodsReceiptReportColumns}
            filterFields={createGoodsReceiptReportFilterFields()}
            initialFilters={createEmptyReportFilters([
                'supplier_id',
                'warehouse_id',
                'product_id',
                'status',
                'start_date',
                'end_date',
            ])}
            endpoint="/api/reports/goods-receipt"
            queryKey={['goods-receipt-report']}
            entityName="Goods Receipt Report"
            exportEndpoint="/api/reports/goods-receipt/export"
            defaultColumnVisibility={{
                'warehouse.name': false,
                item_count: false,
                total_received_quantity: false,
                total_rejected_quantity: false,
                total_accepted_quantity: false,
            }}
            summaryColumns={[
                { key: 'item_count', label: 'Item Count' },
                { key: 'total_received_quantity', label: 'Received Qty' },
                { key: 'total_accepted_quantity', label: 'Accepted Qty' },
                { key: 'total_rejected_quantity', label: 'Rejected Qty' },
                { key: 'total_receipt_value', label: 'Total Value' },
            ]}
        />
    );
}
