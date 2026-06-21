import { ComputedSectionsCard } from '@/components/reports/financial/ComputedSectionsCard';
import { FinancialReportExportButton } from '@/components/reports/financial/FinancialReportExportButton';
import {
    FinancialReportHeaderMeta,
    type FinancialReportFiscalYear,
} from '@/components/reports/financial/FinancialReportPageShell';
import { FinancialStatusBadge } from '@/components/reports/financial/FinancialSummaryCard';
import {
    FinancialTableCard,
    SingleYearFinancialReportPageShell,
    useSingleYearFinancialReportPage,
    type FinancialTableRow,
} from '@/components/reports/financial/FinancialTableReportPage';
import { useExport } from '@/hooks/useExport';
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

const emptyTrialBalanceReport: TrialBalanceResponse['report'] = [];

export default function TrialBalance() {
    const {
        fiscalYears,
        selectedYearId,
        branchId,
        report,
        computedSections,
        selectedFiscalYear,
        handleYearChange,
        handleBranchChange,
        isLoading,
        error,
    } = useSingleYearFinancialReportPage<TrialBalanceResponse['report']>({
        queryKey: 'trial-balance',
        endpoint: 'trial-balance',
        emptyReport: emptyTrialBalanceReport,
    });

    const { exporting, exportData } = useExport({
        endpoint: '/api/reports/trial-balance/export',
    });

    const totalDebit = report.reduce((sum, item) => sum + (item.debit || 0), 0);
    const totalCredit = report.reduce(
        (sum, item) => sum + (item.credit || 0),
        0,
    );
    const difference = Math.abs(totalDebit - totalCredit);
    const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01;

    return (
        <SingleYearFinancialReportPageShell
            title="Trial Balance"
            path="/reports/trial-balance"
            fiscalYears={fiscalYears}
            selectedYearId={selectedYearId}
            onYearChange={handleYearChange}
            branchId={branchId}
            onBranchChange={handleBranchChange}
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
            headerActions={
                <FinancialReportExportButton
                    exporting={exporting}
                    selectedYearId={selectedYearId}
                    branchId={branchId}
                    onExport={exportData}
                />
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
                footerClassName={isBalanced ? undefined : 'text-destructive'}
            >
                {!isBalanced && report.length > 0 && (
                    <div className="mt-3 rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive">
                        Trial Balance tidak seimbang. Selisih:{' '}
                        {formatCurrency(difference)}
                    </div>
                )}
            </FinancialTableCard>

            <ComputedSectionsCard
                sections={computedSections}
                title="Trial Balance Summary"
            />
        </SingleYearFinancialReportPageShell>
    );
}
