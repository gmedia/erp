'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    customerStatementReportColumns,
    type CustomerStatementReportItem,
} from '@/components/reports/customer-statement-report/Columns';
import { createCustomerStatementReportFilterFields } from '@/components/reports/customer-statement-report/Filters';

export default function CustomerStatementReportPage() {
    return (
        <ReportDataTablePage<CustomerStatementReportItem>
            title="Customer Statement Report"
            breadcrumbs={createReportBreadcrumbs(
                'Customer Statement',
                '/reports/customer-statement',
            )}
            columns={customerStatementReportColumns}
            filterFields={createCustomerStatementReportFilterFields()}
            initialFilters={createEmptyReportFilters([
                'customer_id',
                'start_date',
                'end_date',
            ])}
            endpoint="/api/reports/customer-statement"
            queryKey={['customer-statement-report']}
            entityName="Customer Statement Report"
            exportEndpoint="/api/reports/customer-statement/export"
        />
    );
}