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
        />
    );
}
