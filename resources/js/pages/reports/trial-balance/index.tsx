import { ComputedSectionsCard } from '@/components/reports/financial/ComputedSectionsCard';
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
import { Button } from '@/components/ui/button';
import { useExport } from '@/hooks/useExport';
import { formatCurrency } from '@/lib/utils';
import { Download, Loader2 } from 'lucide-react';

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
        report,
        computedSections,
        selectedFiscalYear,
        handleYearChange,
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
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!selectedYearId || exporting}
                    onClick={() =>
                        exportData({
                            fiscal_year_id: String(selectedYearId),
                        })
                    }
                >
                    {exporting ? (
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    ) : (
                        <Download className="mr-2 h-4 w-4" />
                    )}
                    {exporting ? 'Exporting...' : 'Export'}
                </Button>
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

            <ComputedSectionsCard
                sections={computedSections}
                title="Trial Balance Summary"
            />
        </SingleYearFinancialReportPageShell>
    );
}
