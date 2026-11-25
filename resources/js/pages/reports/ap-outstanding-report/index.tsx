'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    apOutstandingReportColumns,
    type ApOutstandingReportItem,
} from '@/components/reports/ap-outstanding-report/Columns';
import { createApOutstandingReportFilterFields } from '@/components/reports/ap-outstanding-report/Filters';

export default function ApOutstandingReportPage() {
    return (
        <ReportDataTablePage<ApOutstandingReportItem>
            title="AP Outstanding Report"
            titleKey="page.ap_outstanding.title"
            descriptionKey="page.ap_outstanding.description"
            breadcrumbs={createReportBreadcrumbs(
                'AP Outstanding',
                '/reports/ap-outstanding',
            )}
            columns={apOutstandingReportColumns}
            filterFields={createApOutstandingReportFilterFields()}
            initialFilters={createEmptyReportFilters([
                'supplier_id',
                'branch_id',
                'status',
                'due_date_from',
                'due_date_to',
            ])}
            endpoint="/api/reports/ap-outstanding"
            queryKey={['ap-outstanding-report']}
            entityName="AP Outstanding Report"
            exportEndpoint="/api/reports/ap-outstanding/export"
            defaultColumnVisibility={{
                branch_name: false,
                amount_paid: false,
            }}
            summaryColumns={[
                { key: 'grand_total', label: 'Grand Total' },
                { key: 'amount_due', label: 'Amount Due' },
            ]}
        />
    );
}
