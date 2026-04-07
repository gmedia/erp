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
    type ReportAccountNode,
} from '@/components/reports/financial/FinancialReportSection';
import {
    FinancialStatusBadge,
    FinancialSummaryCard,
} from '@/components/reports/financial/FinancialSummaryCard';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';
import { formatCurrency } from '@/lib/utils';
import { AlertTriangle } from 'lucide-react';

interface BalanceSheetResponse {
    fiscalYears: FinancialReportFiscalYear[];
    selectedYearId: number;
    comparisonYearId?: number;
    report: {
        assets: ReportAccountNode[];
        liabilities: ReportAccountNode[];
        equity: ReportAccountNode[];
        totals: {
            assets: number;
            liabilities: number;
            equity: number;
            comparison_assets?: number;
            comparison_liabilities?: number;
            comparison_equity?: number;
            change_assets?: number;
            change_percentage_assets?: number;
            change_liabilities?: number;
            change_percentage_liabilities?: number;
            change_equity?: number;
            change_percentage_equity?: number;
        };
    };
}

export default function BalanceSheet() {
    const {
        urlYearId,
        urlComparisonId,
        handleYearChange,
        handleComparisonChange,
    } = useComparisonReportSearchParams();

    const { data, isLoading, error } = useComparisonFinancialReportQuery<
        BalanceSheetResponse['report']
    >('balance-sheet', 'balance-sheet', urlYearId, urlComparisonId);

    const fiscalYears = data?.fiscalYears || [];
    const selectedYearId = data?.selectedYearId || 0;
    const comparisonYearId = data?.comparisonYearId;
    const report = data?.report || {
        assets: [],
        liabilities: [],
        equity: [],
        totals: { assets: 0, liabilities: 0, equity: 0 },
    };

    const { selectedFiscalYear, selectedComparisonFiscalYear } =
        resolveComparisonFiscalYears(
            fiscalYears,
            selectedYearId,
            comparisonYearId,
        );

    // Calculate generic check
    const totalAssets = report.totals?.assets || 0;
    const totalLiabilitiesAndEquity =
        (report.totals?.liabilities || 0) + (report.totals?.equity || 0);
    const isBalanced = Math.abs(totalAssets - totalLiabilitiesAndEquity) < 1;
    const difference = Math.abs(totalAssets - totalLiabilitiesAndEquity);

    return (
        <FinancialReportPageShell
            title="Balance Sheet"
            path="/reports/balance-sheet"
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
                    <FinancialStatusBadge
                        isPositive={isBalanced}
                        positiveLabel="Balanced"
                        negativeLabel={`Unbalanced • ${formatCurrency(difference)}`}
                    />
                </FinancialReportHeaderMeta>
            }
        >
            <div className="grid gap-6">
                <FinancialReportSection
                    title="Assets"
                    nodes={report.assets || []}
                    total={report.totals?.assets || 0}
                    comparisonTotal={report.totals?.comparison_assets}
                    change={report.totals?.change_assets}
                    changePercentage={report.totals?.change_percentage_assets}
                    showComparison={!!comparisonYearId}
                />

                <div className="space-y-6">
                    <FinancialReportSection
                        title="Liabilities"
                        nodes={report.liabilities || []}
                        total={report.totals?.liabilities || 0}
                        comparisonTotal={report.totals?.comparison_liabilities}
                        change={report.totals?.change_liabilities}
                        changePercentage={
                            report.totals?.change_percentage_liabilities
                        }
                        showComparison={!!comparisonYearId}
                    />

                    <FinancialReportSection
                        title="Equity"
                        nodes={report.equity || []}
                        total={report.totals?.equity || 0}
                        comparisonTotal={report.totals?.comparison_equity}
                        change={report.totals?.change_equity}
                        changePercentage={
                            report.totals?.change_percentage_equity
                        }
                        showComparison={!!comparisonYearId}
                    />
                </div>

                <FinancialSummaryCard
                    description="Assets should equal liabilities plus equity."
                    isPositive={isBalanced}
                    status={
                        <FinancialStatusBadge
                            isPositive={isBalanced}
                            positiveLabel="Balanced"
                            negativeLabel={`Unbalanced • ${formatCurrency(difference)}`}
                        />
                    }
                >
                    <div className="grid gap-3 rounded-lg border bg-background p-4">
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-muted-foreground">
                                Total Assets
                            </span>
                            <span className="text-sm font-semibold tabular-nums">
                                {formatCurrency(totalAssets)}
                            </span>
                        </div>
                        <Separator />
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-muted-foreground">
                                Total Liabilities &amp; Equity
                            </span>
                            <span className="text-sm font-semibold tabular-nums">
                                {formatCurrency(totalLiabilitiesAndEquity)}
                            </span>
                        </div>
                    </div>

                    {!isBalanced && (
                        <Alert
                            variant="destructive"
                            className="border-destructive/40 bg-destructive/10 text-destructive"
                        >
                            <AlertTriangle className="h-4 w-4" />
                            <AlertTitle>Unbalanced</AlertTitle>
                            <AlertDescription>
                                Difference:{' '}
                                <span className="font-medium tabular-nums">
                                    {formatCurrency(difference)}
                                </span>
                            </AlertDescription>
                        </Alert>
                    )}
                </FinancialSummaryCard>
            </div>
        </FinancialReportPageShell>
    );
}
