'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    apAgingReportColumns,
    type ApAgingReportItem,
} from '@/components/reports/ap-aging-report/Columns';
import { createApAgingReportFilterFields } from '@/components/reports/ap-aging-report/Filters';

export default function ApAgingReportPage() {
    return (
        <ReportDataTablePage<ApAgingReportItem>
            title="AP Aging Report"
            breadcrumbs={createReportBreadcrumbs(
                'AP Aging',
                '/reports/ap-aging',
            )}
            columns={apAgingReportColumns}
            filterFields={createApAgingReportFilterFields()}
            initialFilters={createEmptyReportFilters([
                'supplier_id',
                'branch_id',
                'status',
                'as_of_date',
            ])}
            endpoint="/api/reports/ap-aging"
            queryKey={['ap-aging-report']}
            entityName="AP Aging Report"
            exportEndpoint="/api/reports/ap-aging/export"
        />
    );
}
