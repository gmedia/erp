'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    trialBalanceDetailedColumns,
    type TrialBalanceDetailedItem,
} from '@/components/reports/trial-balance-detailed/Columns';
import { createTrialBalanceDetailedFilterFields } from '@/components/reports/trial-balance-detailed/Filters';

export default function TrialBalanceDetailedReportPage() {
    return (
        <ReportDataTablePage<TrialBalanceDetailedItem>
            title="Trial Balance Detailed"
            descriptionKey="reports.pageDescriptions.trial-balance-detailed"
            breadcrumbs={createReportBreadcrumbs(
                'Trial Balance Detailed',
                '/reports/trial-balance-detailed',
            )}
            columns={trialBalanceDetailedColumns}
            filterFields={createTrialBalanceDetailedFilterFields()}
            initialFilters={createEmptyReportFilters([
                'fiscal_year_id',
                'period_month',
                'period_year',
            ])}
            endpoint="/api/reports/trial-balance-detailed"
            queryKey={['trial-balance-detailed-report']}
            entityName="Trial Balance Detailed"
            exportEndpoint="/api/reports/trial-balance-detailed/export"
            requiredFilters={['fiscal_year_id']}
            requiredFilterHintKey="reports.selectFilterFiscalYear"
        />
    );
}
