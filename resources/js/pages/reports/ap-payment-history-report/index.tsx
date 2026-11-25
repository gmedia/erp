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
            titleKey="menu.ap_payment_history_report"
            descriptionKey="page.ap_payment_history.description"
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
            summaryColumns={[
                { key: 'total_amount', label: 'Total Amount' },
                { key: 'total_allocated', label: 'Allocated' },
                { key: 'total_unallocated', label: 'Unallocated' },
            ]}
            endpoint="/api/reports/ap-payment-history"
            queryKey={['ap-payment-history-report']}
            entityName="AP Payment History"
            exportEndpoint="/api/reports/ap-payment-history/export"
            defaultColumnVisibility={{
                branch_name: false,
                bank_account: false,
                payment_method: false,
            }}
        />
    );
}
