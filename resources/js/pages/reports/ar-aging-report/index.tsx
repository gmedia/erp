'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    arAgingReportColumns,
    type ArAgingReportItem,
} from '@/components/reports/ar-aging-report/Columns';
import { createArAgingReportFilterFields } from '@/components/reports/ar-aging-report/Filters';

export default function ArAgingReportPage() {
    return (
        <ReportDataTablePage<ArAgingReportItem>
            title="AR Aging Report"
            breadcrumbs={createReportBreadcrumbs(
                'AR Aging',
                '/reports/ar-aging',
            )}
            columns={arAgingReportColumns}
            filterFields={createArAgingReportFilterFields()}
            initialFilters={createEmptyReportFilters([
                'customer_id',
                'branch_id',
                'status',
                'start_date',
                'end_date',
            ])}
            endpoint="/api/reports/ar-aging"
            queryKey={['ar-aging-report']}
            entityName="AR Aging Report"
            exportEndpoint="/api/reports/ar-aging/export"
        />
    );
}
