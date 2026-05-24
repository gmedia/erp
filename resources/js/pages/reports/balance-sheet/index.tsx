import { ComputedSectionsCard } from '@/components/reports/financial/ComputedSectionsCard';
import {
    FinancialReportHeaderMeta,
    FinancialReportPageShell,
    useComparisonFinancialReportPage,
    type FinancialReportFiscalYear,
} from '@/components/reports/financial/FinancialReportPageShell';
import {
    ComparisonFinancialReportSection,
    ComparisonFinancialReportSectionGroup,
    financialPositionSectionConfigs,
    type ReportAccountNode,
} from '@/components/reports/financial/FinancialReportSection';
import {
    FinancialStatusBadge,
    FinancialSummaryCard,
} from '@/components/reports/financial/FinancialSummaryCard';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useExport } from '@/hooks/useExport';
import { formatCurrency } from '@/lib/utils';
import { AlertTriangle, Download, Loader2 } from 'lucide-react';

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

const emptyBalanceSheetReport: BalanceSheetResponse['report'] = {
    assets: [],
    liabilities: [],
    equity: [],
    totals: { assets: 0, liabilities: 0, equity: 0 },
};

const balanceSheetSecondarySections = financialPositionSectionConfigs.slice(1);

export default function BalanceSheet() {
    const {
        fiscalYears,
        selectedYearId,
        comparisonYearId,
        report,
        computedSections,
        selectedFiscalYear,
        selectedComparisonFiscalYear,
        handleYearChange,
        handleComparisonChange,
        isLoading,
        error,
    } = useComparisonFinancialReportPage<BalanceSheetResponse['report']>({
        queryKey: 'balance-sheet',
        endpoint: 'balance-sheet',
        emptyReport: emptyBalanceSheetReport,
    });

    const { exporting, exportData } = useExport({
        endpoint: '/api/reports/balance-sheet/export',
    });

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
            headerActions={
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!selectedYearId || exporting}
                    onClick={() =>
                        exportData({
                            fiscal_year_id: String(selectedYearId),
                            ...(comparisonYearId && {
                                comparison_year_id: String(comparisonYearId),
                            }),
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
            <div className="grid gap-6">
                <ComparisonFinancialReportSection
                    title={financialPositionSectionConfigs[0].title}
                    metric={financialPositionSectionConfigs[0].metric}
                    nodes={report.assets || []}
                    totals={report.totals}
                    showComparison={!!comparisonYearId}
                />

                <ComparisonFinancialReportSectionGroup
                    className="space-y-6"
                    sections={balanceSheetSecondarySections}
                    report={report}
                    totals={report.totals}
                    showComparison={!!comparisonYearId}
                />

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

                <ComputedSectionsCard
                    sections={computedSections}
                    title="Financial Position Summary"
                />
            </div>
        </FinancialReportPageShell>
    );
}
