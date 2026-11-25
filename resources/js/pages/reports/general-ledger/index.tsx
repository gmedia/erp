'use client';

import {
    createEmptyReportFilters,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    generalLedgerColumns,
    type GeneralLedgerItem,
} from '@/components/reports/general-ledger/Columns';
import { createGeneralLedgerFilterFields } from '@/components/reports/general-ledger/Filters';

export default function GeneralLedgerReportPage() {
    return (
        <ReportDataTablePage<GeneralLedgerItem>
            title="General Ledger Report"
            titleKey="page.general_ledger.title"
            descriptionKey="page.general_ledger.description"
            breadcrumbs={[
                {
                    title: 'Reports',
                    titleKey: 'reports.breadcrumbGroup',
                    href: '#',
                },
                {
                    title: 'General Ledger',
                    titleKey: 'menu.general_ledger_report',
                    href: '/reports/general-ledger',
                },
            ]}
            columns={generalLedgerColumns}
            filterFields={createGeneralLedgerFilterFields()}
            initialFilters={createEmptyReportFilters([
                'account_id',
                'fiscal_year_id',
                'start_date',
                'end_date',
                'journal_type',
            ] as const)}
            endpoint="/api/reports/general-ledger"
            queryKey={['general-ledger-report']}
            entityName="General Ledger"
            exportEndpoint="/api/reports/general-ledger/export"
            requiredFilters={['account_id']}
            requiredFilterHintKey="reports.selectFilterAccount"
        />
    );
}
