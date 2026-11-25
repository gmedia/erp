'use client';

import {
    createEmptyReportFilters,
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
            titleKey="menu.ap_aging_report"
            descriptionKey="reports.pageDescriptions.ap-aging"
            breadcrumbs={[
                {
                    title: 'Reports',
                    titleKey: 'reports.breadcrumbGroup',
                    href: '#',
                },
                {
                    title: 'AP Aging Report',
                    titleKey: 'menu.ap_aging_report',
                    href: '/reports/ap-aging',
                },
            ]}
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
                {
                    key: 'amount_due',
                    label: 'Amount Due',
                    columnId: 'amount_due',
                },
                {
                    key: 'current_amount',
                    label: 'Current',
                    columnId: 'current',
                },
                { key: 'days_1_30', label: '1-30 Days', columnId: 'days_1_30' },
                {
                    key: 'days_31_60',
                    label: '31-60 Days',
                    columnId: 'days_31_60',
                },
                {
                    key: 'days_61_90',
                    label: '61-90 Days',
                    columnId: 'days_61_90',
                },
                {
                    key: 'days_over_90',
                    label: '>90 Days',
                    columnId: 'days_over_90',
                },
            ]}
        />
    );
}
