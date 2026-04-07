import {
    FinancialReportHeaderMeta,
    FinancialReportPageShell,
    resolveComparisonFiscalYears,
    useComparisonFinancialReportQuery,
    useComparisonReportSearchParams,
    type FinancialReportFiscalYear,
} from '@/components/reports/financial/FinancialReportPageShell';
import {
    FinancialReportSection,
    getChangeTextClass,
    type ReportAccountNode,
} from '@/components/reports/financial/FinancialReportSection';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { cn, formatCurrency } from '@/lib/utils';

interface IncomeStatementResponse {
    fiscalYears: FinancialReportFiscalYear[];
    selectedYearId: number;
    comparisonYearId?: number;
    report: {
        revenues: ReportAccountNode[];
        expenses: ReportAccountNode[];
        totals: {
            revenue: number;
            expense: number;
            net_income: number;
            comparison_revenue?: number;
            comparison_expense?: number;
            comparison_net_income?: number;
            change_revenue?: number;
            change_percentage_revenue?: number;
            change_expense?: number;
            change_percentage_expense?: number;
            change_net_income?: number;
            change_percentage_net_income?: number;
        };
    };
}

export default function IncomeStatement() {
    const {
        urlYearId,
        urlComparisonId,
        handleYearChange,
        handleComparisonChange,
    } = useComparisonReportSearchParams();

    const { data, isLoading, error } = useComparisonFinancialReportQuery<
        IncomeStatementResponse['report']
    >('income-statement', 'income-statement', urlYearId, urlComparisonId);

    const fiscalYears = data?.fiscalYears || [];
    const selectedYearId = data?.selectedYearId || 0;
    const comparisonYearId = data?.comparisonYearId;
    const report = data?.report || {
        revenues: [],
        expenses: [],
        totals: { revenue: 0, expense: 0, net_income: 0 },
    };

    const { selectedFiscalYear, selectedComparisonFiscalYear } =
        resolveComparisonFiscalYears(
            fiscalYears,
            selectedYearId,
            comparisonYearId,
        );

    const totalRevenue = report.totals?.revenue || 0;
    const totalExpense = report.totals?.expense || 0;
    const netIncome = report.totals?.net_income || 0;
    const netIncomeComparison = report.totals?.comparison_net_income || 0;
    const netIncomeChange = report.totals?.change_net_income || 0;
    const netIncomeChangePct = report.totals?.change_percentage_net_income || 0;
    const isProfit = netIncome >= 0;

    return (
        <FinancialReportPageShell
            title="Income Statement"
            path="/reports/income-statement"
            fiscalYears={fiscalYears}
            selectedYearId={selectedYearId}
            comparisonYearId={comparisonYearId}
            onYearChange={handleYearChange}
            onComparisonChange={(value) =>
                handleComparisonChange(value, selectedYearId)
            }
            isLoading={isLoading}
            hasError={!!error}
            headerMeta={
                <FinancialReportHeaderMeta
                    fiscalYear={selectedFiscalYear}
                    comparisonFiscalYear={selectedComparisonFiscalYear}
                    showComparisonBadge
                >
                    <Badge
                        variant={isProfit ? 'secondary' : 'destructive'}
                        className={cn(
                            isProfit &&
                                'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
                        )}
                    >
                        {isProfit ? 'Profit' : 'Loss'}
                    </Badge>
                </FinancialReportHeaderMeta>
            }
        >
            <div className="grid gap-6">
                <FinancialReportSection
                    title="Revenue"
                    nodes={report.revenues || []}
                    total={report.totals?.revenue || 0}
                    comparisonTotal={report.totals?.comparison_revenue}
                    change={report.totals?.change_revenue}
                    changePercentage={report.totals?.change_percentage_revenue}
                    showComparison={!!comparisonYearId}
                />

                <FinancialReportSection
                    title="Expense"
                    nodes={report.expenses || []}
                    total={report.totals?.expense || 0}
                    comparisonTotal={report.totals?.comparison_expense}
                    change={report.totals?.change_expense}
                    changePercentage={report.totals?.change_percentage_expense}
                    showComparison={!!comparisonYearId}
                />

                <Card
                    className={cn(
                        'overflow-hidden border-t-4',
                        isProfit ? 'border-emerald-500' : 'border-destructive',
                    )}
                >
                    <CardHeader className="bg-muted/15">
                        <div className="flex items-start justify-between gap-3">
                            <div className="space-y-1">
                                <CardTitle className="text-base">
                                    Summary
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Net Income = Total Revenue - Total Expense.
                                </CardDescription>
                            </div>
                            <Badge
                                variant={isProfit ? 'secondary' : 'destructive'}
                                className={cn(
                                    isProfit &&
                                        'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
                                )}
                            >
                                {isProfit ? 'Profit' : 'Loss'}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="grid gap-3 rounded-lg border bg-background p-4">
                            <div className="flex items-center justify-between gap-4">
                                <span className="text-sm text-muted-foreground">
                                    Total Revenue
                                </span>
                                <span className="text-sm font-semibold tabular-nums">
                                    {formatCurrency(totalRevenue)}
                                </span>
                            </div>
                            <Separator />
                            <div className="flex items-center justify-between gap-4">
                                <span className="text-sm text-muted-foreground">
                                    Total Expense
                                </span>
                                <span className="text-sm font-semibold tabular-nums">
                                    {formatCurrency(totalExpense)}
                                </span>
                            </div>
                            <Separator />
                            <div className="flex items-center justify-between gap-4">
                                <span className="text-sm text-muted-foreground">
                                    Net Income
                                </span>
                                <span
                                    className={cn(
                                        'text-sm font-semibold tabular-nums',
                                        isProfit
                                            ? 'text-emerald-700 dark:text-emerald-300'
                                            : 'text-destructive',
                                    )}
                                >
                                    {formatCurrency(netIncome)}
                                </span>
                            </div>
                            {!!comparisonYearId && (
                                <>
                                    <Separator />
                                    <div className="flex items-center justify-between gap-4">
                                        <span className="text-sm text-muted-foreground">
                                            Net Income (Comparison)
                                        </span>
                                        <span className="text-sm font-semibold text-muted-foreground tabular-nums">
                                            {formatCurrency(
                                                netIncomeComparison,
                                            )}
                                        </span>
                                    </div>
                                    <Separator />
                                    <div className="flex items-center justify-between gap-4">
                                        <span className="text-sm text-muted-foreground">
                                            Net Income Change
                                        </span>
                                        <span
                                            className={cn(
                                                'text-sm font-semibold tabular-nums',
                                                getChangeTextClass(
                                                    netIncomeChange,
                                                ),
                                            )}
                                        >
                                            {formatCurrency(netIncomeChange)} (
                                            {netIncomeChangePct.toFixed(1)}
                                            %)
                                        </span>
                                    </div>
                                </>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </FinancialReportPageShell>
    );
}
