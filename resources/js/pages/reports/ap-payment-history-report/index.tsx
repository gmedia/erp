'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    apPaymentHistoryReportColumns,
    type ApPaymentHistoryReportItem,
} from '@/components/reports/ap-payment-history-report/Columns';
import { createApPaymentHistoryReportFilterFields } from '@/components/reports/ap-payment-history-report/Filters';

export default function ApPaymentHistoryReportPage() {
    return (
        <ReportDataTablePage<ApPaymentHistoryReportItem>
            title="AP Payment History"
            breadcrumbs={createReportBreadcrumbs(
                'AP Payment History',
                '/reports/ap-payment-history',
            )}
            columns={apPaymentHistoryReportColumns}
            filterFields={createApPaymentHistoryReportFilterFields()}
            initialFilters={createEmptyReportFilters([
                'supplier_id',
                'branch_id',
                'payment_method',
                'status',
                'payment_date_from',
                'payment_date_to',
            ])}
            endpoint="/api/reports/ap-payment-history"
            queryKey={['ap-payment-history-report']}
            entityName="AP Payment History"
            exportEndpoint="/api/reports/ap-payment-history/export"
        />
    );
}