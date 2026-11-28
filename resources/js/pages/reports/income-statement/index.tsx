import { ComputedSectionsCard } from '@/components/reports/financial/ComputedSectionsCard';
import { FinancialReportExportButton } from '@/components/reports/financial/FinancialReportExportButton';
import {
    FinancialReportHeaderMeta,
    FinancialReportPageShell,
    useComparisonFinancialReportPage,
    type FinancialReportFiscalYear,
} from '@/components/reports/financial/FinancialReportPageShell';
import {
    FinancialReportSection,
    getChangeTextClass,
    type ReportAccountNode,
} from '@/components/reports/financial/FinancialReportSection';
import {
    FinancialStatusBadge,
    FinancialSummaryCard,
} from '@/components/reports/financial/FinancialSummaryCard';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/contexts/i18n-context';
import { useExport } from '@/hooks/useExport';
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

const emptyIncomeStatementReport: IncomeStatementResponse['report'] = {
    revenues: [],
    expenses: [],
    totals: { revenue: 0, expense: 0, net_income: 0 },
};

export default function IncomeStatement() {
    const { t } = useTranslation();
    const {
        fiscalYears,
        selectedYearId,
        comparisonYearId,
        branchId,
        departmentId,
        report,
        computedSections,
        selectedFiscalYear,
        selectedComparisonFiscalYear,
        handleYearChange,
        handleComparisonChange,
        handleBranchChange,
        handleDepartmentChange,
        isLoading,
        error,
    } = useComparisonFinancialReportPage<IncomeStatementResponse['report']>({
        queryKey: 'income-statement',
        endpoint: 'income-statement',
        emptyReport: emptyIncomeStatementReport,
    });

    const { exporting, exportData } = useExport({
        endpoint: '/api/reports/income-statement/export',
    });

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
            titleKey="menu.income_statement_report"
            descriptionKey="page.income_statement.description"
            path="/reports/income-statement"
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
            departmentId={departmentId}
            onDepartmentChange={(value) =>
                handleDepartmentChange(value, selectedYearId)
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
                        isPositive={isProfit}
                        positiveLabel={t('reports.financial.status.profit')}
                        negativeLabel={t('reports.financial.status.loss')}
                    />
                </FinancialReportHeaderMeta>
            }
            headerActions={
                <FinancialReportExportButton
                    exporting={exporting}
                    selectedYearId={selectedYearId}
                    comparisonYearId={comparisonYearId}
                    branchId={branchId}
                    departmentId={departmentId}
                    onExport={exportData}
                />
            }
        >
            <div className="grid min-w-0 gap-6">
                <FinancialReportSection
                    title="Revenue"
                    titleKey="reports.financial.section.revenue"
                    nodes={report.revenues || []}
                    total={report.totals?.revenue || 0}
                    comparisonTotal={report.totals?.comparison_revenue}
                    change={report.totals?.change_revenue}
                    changePercentage={report.totals?.change_percentage_revenue}
                    showComparison={!!comparisonYearId}
                />

                <FinancialReportSection
                    title="Expense"
                    titleKey="reports.financial.section.expense"
                    nodes={report.expenses || []}
                    total={report.totals?.expense || 0}
                    comparisonTotal={report.totals?.comparison_expense}
                    change={report.totals?.change_expense}
                    changePercentage={report.totals?.change_percentage_expense}
                    showComparison={!!comparisonYearId}
                />

                <FinancialSummaryCard
                    description={t(
                        'reports.financial.income_statement.description',
                    )}
                    hideStatusBorder
                    isPositive={isProfit}
                    status={
                        <FinancialStatusBadge
                            isPositive={isProfit}
                            positiveLabel={t('reports.financial.status.profit')}
                            negativeLabel={t('reports.financial.status.loss')}
                        />
                    }
                >
                    <div className="grid gap-3 rounded-lg border bg-background p-4">
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-muted-foreground">
                                {t(
                                    'reports.financial.income_statement.total_revenue',
                                )}
                            </span>
                            <span className="text-sm font-semibold tabular-nums">
                                {formatCurrency(totalRevenue)}
                            </span>
                        </div>
                        <Separator />
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-muted-foreground">
                                {t(
                                    'reports.financial.income_statement.total_expense',
                                )}
                            </span>
                            <span className="text-sm font-semibold tabular-nums">
                                {formatCurrency(totalExpense)}
                            </span>
                        </div>
                        <Separator />
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-muted-foreground">
                                {t(
                                    'reports.financial.income_statement.net_income',
                                )}
                            </span>
                            <span
                                className={cn(
                                    'text-sm font-semibold tabular-nums',
                                    isProfit
                                        ? 'text-success'
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
                                        {t(
                                            'reports.financial.income_statement.net_income_comparison',
                                        )}
                                    </span>
                                    <span className="text-sm font-semibold text-muted-foreground tabular-nums">
                                        {formatCurrency(netIncomeComparison)}
                                    </span>
                                </div>
                                <Separator />
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-sm text-muted-foreground">
                                        {t(
                                            'reports.financial.income_statement.net_income_change',
                                        )}
                                    </span>
                                    <span
                                        className={cn(
                                            'text-sm font-semibold tabular-nums',
                                            getChangeTextClass(netIncomeChange),
                                        )}
                                    >
                                        {formatCurrency(netIncomeChange)} (
                                        {netIncomeChangePct.toFixed(1)}%)
                                    </span>
                                </div>
                            </>
                        )}
                    </div>
                </FinancialSummaryCard>

                <ComputedSectionsCard
                    sections={computedSections}
                    title="Income Summary"
                    titleKey="reports.financial.income_statement.summary"
                />
            </div>
        </FinancialReportPageShell>
    );
}
