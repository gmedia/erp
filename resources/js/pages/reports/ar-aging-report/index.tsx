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
            descriptionKey="reports.pageDescriptions.ar-aging"
            breadcrumbs={createReportBreadcrumbs(
                'AR Aging Report',
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
            defaultColumnVisibility={{
                grand_total: false,
                amount_received: false,
                credit_note_amount: false,
                amount_due: false,
            }}
            summaryColumns={[
                {
                    key: 'amount_due',
                    label: 'Amount Due',
                    columnId: 'amount_due',
                },
                { key: 'aging_current', label: 'Current', columnId: 'current' },
                { key: 'aging_1_30', label: '1-30 Days', columnId: '1_30' },
                { key: 'aging_31_60', label: '31-60 Days', columnId: '31_60' },
                { key: 'aging_61_90', label: '61-90 Days', columnId: '61_90' },
                {
                    key: 'aging_over_90',
                    label: 'Over 90 Days',
                    columnId: 'over_90',
                },
            ]}
        />
    );
}
