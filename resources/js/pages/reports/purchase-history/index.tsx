'use client';

import {
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
            breadcrumbs={createReportBreadcrumbs(
                'Purchase History',
                '/reports/purchase-history',
            )}
            columns={purchaseHistoryReportColumns}
            filterFields={createPurchaseHistoryReportFilterFields()}
            initialFilters={{
                search: '',
                supplier_id: '',
                warehouse_id: '',
                product_id: '',
                status: '',
                start_date: '',
                end_date: '',
            }}
            endpoint="/api/reports/purchase-history"
            queryKey={['purchase-history-report']}
            entityName="Purchase History Report"
            exportEndpoint="/api/reports/purchase-history/export"
        />
    );
}
