'use client';

import {
    createEmptyReportFilters,
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
            titleKey="menu.trial_balance_detailed_report"
            descriptionKey="page.trial_balance_detailed.description"
            breadcrumbs={[
                {
                    title: 'Reports',
                    titleKey: 'reports.breadcrumbGroup',
                    href: '#',
                },
                {
                    title: 'Trial Balance Detailed',
                    titleKey: 'menu.trial_balance_detailed_report',
                    href: '/reports/trial-balance-detailed',
                },
            ]}
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
