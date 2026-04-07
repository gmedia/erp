import {
    FinancialReportHeaderMeta,
    type FinancialReportFiscalYear,
} from '@/components/reports/financial/FinancialReportPageShell';
import { FinancialStatusBadge } from '@/components/reports/financial/FinancialSummaryCard';
import {
    FinancialTableCard,
    resolveSelectedFiscalYear,
    SingleYearFinancialReportPageShell,
    useSingleYearFinancialReportQuery,
    useSingleYearReportSearchParams,
    type FinancialTableRow,
} from '@/components/reports/financial/FinancialTableReportPage';
import { formatCurrency } from '@/lib/utils';

interface AccountItem extends FinancialTableRow {
    id: number;
    code: string;
    name: string;
    type: string;
    level: number;
    parent_id: number | null;
    normal_balance: 'debit' | 'credit';
    debit: number;
    credit: number;
}

interface TrialBalanceResponse {
    fiscalYears: FinancialReportFiscalYear[];
    selectedYearId: number;
    report: AccountItem[];
}

export default function TrialBalance() {
    const { urlYearId, handleYearChange } = useSingleYearReportSearchParams();

    const { data, isLoading, error } = useSingleYearFinancialReportQuery<
        TrialBalanceResponse['report']
    >('trial-balance', 'trial-balance', urlYearId);

    const fiscalYears = Array.isArray(data?.fiscalYears)
        ? data.fiscalYears
        : [];
    const selectedYearId = data?.selectedYearId || 0;
    const report = Array.isArray(data?.report) ? data.report : [];

    const totalDebit = report.reduce((sum, item) => sum + (item.debit || 0), 0);
    const totalCredit = report.reduce(
        (sum, item) => sum + (item.credit || 0),
        0,
    );
    const difference = Math.abs(totalDebit - totalCredit);
    const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01;

    const selectedFiscalYear = resolveSelectedFiscalYear(
        fiscalYears,
        selectedYearId,
    );

    return (
        <SingleYearFinancialReportPageShell
            title="Trial Balance"
            path="/reports/trial-balance"
            fiscalYears={fiscalYears}
            selectedYearId={selectedYearId}
            onYearChange={handleYearChange}
            isLoading={isLoading}
            hasError={!!error}
            headerMeta={
                <FinancialReportHeaderMeta fiscalYear={selectedFiscalYear}>
                    {report.length > 0 && (
                        <FinancialStatusBadge
                            isPositive={isBalanced}
                            positiveLabel="Balanced"
                            negativeLabel={`Unbalanced • ${formatCurrency(difference)}`}
                        />
                    )}
                </FinancialReportHeaderMeta>
            }
        >
            <FinancialTableCard
                title="Trial Balance Report"
                items={report}
                amountColumns={[
                    {
                        header: 'Debit',
                        total: totalDebit,
                        value: (item) => item.debit,
                    },
                    {
                        header: 'Credit',
                        total: totalCredit,
                        value: (item) => item.credit,
                    },
                ]}
                emptyMessage="No data available for the selected fiscal year."
                scrollAreaClassName="max-h-[calc(100vh-18rem)]"
                footerClassName={!isBalanced ? 'text-destructive' : undefined}
            >
                {!isBalanced && report.length > 0 && (
                    <div className="mt-3 rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive">
                        Trial Balance tidak seimbang. Selisih:{' '}
                        {formatCurrency(difference)}
                    </div>
                )}
            </FinancialTableCard>
        </SingleYearFinancialReportPageShell>
    );
}
