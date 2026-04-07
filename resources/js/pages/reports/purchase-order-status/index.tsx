'use client';

import {
    createEmptyReportFilters,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    purchaseOrderStatusReportColumns,
    type PurchaseOrderStatusReportItem,
} from '@/components/reports/purchase-order-status/Columns';
import { createPurchaseOrderStatusReportFilterFields } from '@/components/reports/purchase-order-status/Filters';

export default function PurchaseOrderStatusReportPage() {
    return (
        <ReportDataTablePage<PurchaseOrderStatusReportItem>
            title="Purchase Order Status Report"
            breadcrumbs={[
                { title: 'Reports', href: '#' },
                {
                    title: 'Purchase Order Status',
                    href: '/reports/purchase-order-status',
                },
            ]}
            columns={purchaseOrderStatusReportColumns}
            filterFields={createPurchaseOrderStatusReportFilterFields()}
            initialFilters={createEmptyReportFilters([
                'supplier_id',
                'warehouse_id',
                'product_id',
                'status',
                'status_category',
                'start_date',
                'end_date',
            ])}
            endpoint="/api/reports/purchase-order-status"
            queryKey={['purchase-order-status-report']}
            entityName="Purchase Order Status Report"
            exportEndpoint="/api/reports/purchase-order-status/export"
        />
    );
}
