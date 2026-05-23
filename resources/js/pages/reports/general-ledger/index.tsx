'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
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
            breadcrumbs={createReportBreadcrumbs(
                'General Ledger',
                '/reports/general-ledger',
            )}
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
        />
    );
}
