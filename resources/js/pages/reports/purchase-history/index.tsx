'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    purchaseHistoryReportColumns,
    type PurchaseHistoryReportItem,
} from '@/components/reports/purchase-history/Columns';
import { createPurchaseHistoryReportFilterFields } from '@/components/reports/purchase-history/Filters';

export default function PurchaseHistoryReportPage() {
    return (
        <ReportDataTablePage<PurchaseHistoryReportItem>
            title="Purchase History Report"
            descriptionKey="reports.pageDescriptions.purchase-history"
            breadcrumbs={createReportBreadcrumbs(
                'Purchase History',
                '/reports/purchase-history',
            )}
            columns={purchaseHistoryReportColumns}
            filterFields={createPurchaseHistoryReportFilterFields()}
            initialFilters={createEmptyReportFilters([
                'supplier_id',
                'warehouse_id',
                'product_id',
                'status',
                'start_date',
                'end_date',
            ])}
            endpoint="/api/reports/purchase-history"
            queryKey={['purchase-history-report']}
            entityName="Purchase History Report"
            exportEndpoint="/api/reports/purchase-history/export"
            defaultColumnVisibility={{
                warehouse_name: false,
                purchase_order_expected_delivery_date: false,
                receipt_count: false,
                goods_receipt_last_receipt_date: false,
            }}
            summaryColumns={[
                { key: 'ordered_quantity', label: 'Ordered Qty' },
                { key: 'received_quantity', label: 'Received Qty' },
                { key: 'outstanding_quantity', label: 'Outstanding Qty' },
                { key: 'total_purchase_value', label: 'Total Value' },
            ]}
        />
    );
}
