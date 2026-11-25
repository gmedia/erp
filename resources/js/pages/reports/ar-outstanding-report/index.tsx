'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    arOutstandingReportColumns,
    type ArOutstandingReportItem,
} from '@/components/reports/ar-outstanding-report/Columns';
import { createArOutstandingReportFilterFields } from '@/components/reports/ar-outstanding-report/Filters';

export default function ArOutstandingReportPage() {
    return (
        <ReportDataTablePage<ArOutstandingReportItem>
            title="AR Outstanding Report"
            titleKey="page.ar_outstanding.title"
            descriptionKey="page.ar_outstanding.description"
            breadcrumbs={createReportBreadcrumbs(
                'AR Outstanding',
                '/reports/ar-outstanding',
            )}
            columns={arOutstandingReportColumns}
            filterFields={createArOutstandingReportFilterFields()}
            initialFilters={createEmptyReportFilters([
                'customer_id',
                'branch_id',
                'status',
                'start_date',
                'end_date',
            ])}
            endpoint="/api/reports/ar-outstanding"
            queryKey={['ar-outstanding-report']}
            entityName="AR Outstanding Report"
            exportEndpoint="/api/reports/ar-outstanding/export"
            summaryColumns={[
                { key: 'grand_total', label: 'Grand Total' },
                { key: 'amount_received', label: 'Amount Received' },
                { key: 'credit_note_amount', label: 'Credit Note Amount' },
                { key: 'amount_due', label: 'Amount Due' },
            ]}
            defaultColumnVisibility={{
                'branch.name': false,
                grand_total: false,
                amount_received: false,
                credit_note_amount: false,
            }}
        />
    );
}
