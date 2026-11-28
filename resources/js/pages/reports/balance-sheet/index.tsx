import { PageBanner } from '@/components/common/PageBanner';
import { ComputedSectionsCard } from '@/components/reports/financial/ComputedSectionsCard';
import { FinancialReportExportButton } from '@/components/reports/financial/FinancialReportExportButton';
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
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/contexts/i18n-context';
import { useExport } from '@/hooks/useExport';
import { formatCurrency } from '@/lib/utils';

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
    const { t } = useTranslation();
    const {
        fiscalYears,
        selectedYearId,
        comparisonYearId,
        branchId,
        report,
        computedSections,
        selectedFiscalYear,
        selectedComparisonFiscalYear,
        handleYearChange,
        handleComparisonChange,
        handleBranchChange,
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
            titleKey="menu.balance_sheet_report"
            descriptionKey="page.balance_sheet.description"
            path="/reports/balance-sheet"
            fiscalYears={fiscalYears}
            selectedYearId={selectedYearId}
            comparisonYearId={comparisonYearId}
            onYearChange={handleYearChange}
            onComparisonChange={(value) =>
                handleComparisonChange(value, selectedYearId)
            }
            branchId={branchId}
            onBranchChange={(value) =>
                handleBranchChange(value, selectedYearId)
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
                        positiveLabel={t(
                            'reports.financial.balance_sheet.balanced',
                        )}
                        negativeLabel={`${t(
                            'reports.financial.balance_sheet.unbalanced',
                        )} • ${formatCurrency(difference)}`}
                    />
                </FinancialReportHeaderMeta>
            }
            headerActions={
                <FinancialReportExportButton
                    exporting={exporting}
                    selectedYearId={selectedYearId}
                    comparisonYearId={comparisonYearId}
                    branchId={branchId}
                    onExport={exportData}
                />
            }
        >
            <div className="grid min-w-0 gap-6">
                <ComparisonFinancialReportSection
                    title={financialPositionSectionConfigs[0]?.title ?? ''}
                    titleKey={financialPositionSectionConfigs[0]?.titleKey}
                    metric={
                        financialPositionSectionConfigs[0]?.metric ?? 'assets'
                    }
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
                    description={t(
                        'reports.financial.balance_sheet.description',
                    )}
                    isPositive={isBalanced}
                    status={
                        <FinancialStatusBadge
                            isPositive={isBalanced}
                            positiveLabel={t(
                                'reports.financial.balance_sheet.balanced',
                            )}
                            negativeLabel={`${t(
                                'reports.financial.balance_sheet.unbalanced',
                            )} • ${formatCurrency(difference)}`}
                        />
                    }
                >
                    <div className="grid gap-3 rounded-lg border bg-background p-4">
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-muted-foreground">
                                {t(
                                    'reports.financial.balance_sheet.total_assets',
                                )}
                            </span>
                            <span className="text-sm font-semibold tabular-nums">
                                {formatCurrency(totalAssets)}
                            </span>
                        </div>
                        <Separator />
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-muted-foreground">
                                {t(
                                    'reports.financial.balance_sheet.total_liabilities_equity',
                                )}
                            </span>
                            <span className="text-sm font-semibold tabular-nums">
                                {formatCurrency(totalLiabilitiesAndEquity)}
                            </span>
                        </div>
                    </div>

                    {!isBalanced && (
                        <PageBanner
                            tone="destructive"
                            title={t(
                                'reports.financial.balance_sheet.unbalanced',
                            )}
                        >
                            {t('reports.financial.balance_sheet.difference')}:{' '}
                            <span className="font-medium tabular-nums">
                                {formatCurrency(difference)}
                            </span>
                        </PageBanner>
                    )}
                </FinancialSummaryCard>

                <ComputedSectionsCard
                    sections={computedSections}
                    title="Financial Position Summary"
                    titleKey="reports.financial.balance_sheet.position_summary"
                />
            </div>
        </FinancialReportPageShell>
    );
}
