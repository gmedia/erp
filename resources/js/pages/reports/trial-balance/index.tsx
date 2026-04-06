import type { FinancialReportFiscalYear } from '@/components/reports/financial/FinancialReportPageShell';
import {
    FinancialTableCard,
    SingleYearFinancialReportPageShell,
    useSingleYearReportSearchParams,
    type FinancialTableRow,
} from '@/components/reports/financial/FinancialTableReportPage';
import { Badge } from '@/components/ui/badge';
import axios from '@/lib/axios';
import { cn, formatCurrency } from '@/lib/utils';
import { useQuery } from '@tanstack/react-query';

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

    const { data, isLoading, error } = useQuery<TrialBalanceResponse>({
        queryKey: ['trial-balance', urlYearId],
        queryFn: async () => {
            const params = new URLSearchParams();
            if (urlYearId) {
                params.append('fiscal_year_id', urlYearId);
            }
            const response = await axios.get(
                `/api/reports/trial-balance?${params.toString()}`,
            );
            return response.data;
        },
    });

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

    const selectedFiscalYear = fiscalYears.find(
        (fy) => fy.id === selectedYearId,
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
                <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                    {selectedFiscalYear && (
                        <span>
                            {selectedFiscalYear.name} •{' '}
                            {selectedFiscalYear.status}
                        </span>
                    )}
                    {report.length > 0 && (
                        <Badge
                            variant={isBalanced ? 'secondary' : 'destructive'}
                            className={cn(
                                isBalanced &&
                                    'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
                            )}
                        >
                            {isBalanced
                                ? 'Balanced'
                                : `Unbalanced • ${formatCurrency(difference)}`}
                        </Badge>
                    )}
                </div>
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
