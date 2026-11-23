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
            descriptionKey="reports.pageDescriptions.ap-aging"
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
            defaultColumnVisibility={{
                branch_name: false,
                grand_total: false,
                bill_status: false,
            }}
            summaryColumns={[
                { key: 'amount_due', label: 'Amount Due' },
                { key: 'current_amount', label: 'Current' },
                { key: 'days_1_30', label: '1-30 Days' },
                { key: 'days_31_60', label: '31-60 Days' },
                { key: 'days_61_90', label: '61-90 Days' },
                { key: 'days_over_90', label: '>90 Days' },
            ]}
        />
    );
}
