import {
    FinancialReportHeaderMeta,
    FinancialReportPageShell,
    useComparisonFinancialReportPage,
    type FinancialReportFiscalYear,
} from '@/components/reports/financial/FinancialReportPageShell';
import {
    ComparisonFinancialReportSectionGroup,
    FinancialReportSection,
    financialPositionSectionConfigs,
    type ReportAccountNode,
} from '@/components/reports/financial/FinancialReportSection';
interface ComparativeReportResponse {
    fiscalYears: FinancialReportFiscalYear[];
    selectedYearId: number;
    comparisonYearId?: number;
    report: {
        assets: ReportAccountNode[];
        liabilities: ReportAccountNode[];
        equity: ReportAccountNode[];
        revenues: ReportAccountNode[];
        expenses: ReportAccountNode[];
        totals: {
            assets: number;
            liabilities: number;
            equity: number;
            revenues: number;
            expenses: number;
            comparison_assets?: number;
            comparison_liabilities?: number;
            comparison_equity?: number;
            comparison_revenues?: number;
            comparison_expenses?: number;
            change_assets?: number;
            change_liabilities?: number;
            change_equity?: number;
            change_revenues?: number;
            change_expenses?: number;
            change_percentage_assets?: number;
            change_percentage_liabilities?: number;
            change_percentage_equity?: number;
            change_percentage_revenues?: number;
            change_percentage_expenses?: number;
        };
    };
}

const emptyComparativeReport: ComparativeReportResponse['report'] = {
    assets: [],
    liabilities: [],
    equity: [],
    revenues: [],
    expenses: [],
    totals: {
        assets: 0,
        liabilities: 0,
        equity: 0,
        revenues: 0,
        expenses: 0,
    },
};

const comparativePerformanceSections = [
    { title: 'Revenue', metric: 'revenues' },
    { title: 'Expense', metric: 'expenses' },
] as const;

export default function ComparativeReport() {
    const {
        fiscalYears,
        selectedYearId,
        comparisonYearId,
        report,
        selectedFiscalYear,
        selectedComparisonFiscalYear,
        handleYearChange,
        handleComparisonChange,
        isLoading,
        error,
    } = useComparisonFinancialReportPage<ComparativeReportResponse['report']>({
        queryKey: 'comparative-report',
        endpoint: 'comparative',
        emptyReport: emptyComparativeReport,
    });

    return (
        <FinancialReportPageShell
            title="Comparative Report"
            path="/reports/comparative"
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
                />
            }
        >
            <div className="grid gap-6">
                <ComparisonFinancialReportSectionGroup
                    sections={financialPositionSectionConfigs}
                    report={report}
                    totals={report.totals}
                    showComparison={!!comparisonYearId}
                />
                <ComparisonFinancialReportSectionGroup
                    sections={comparativePerformanceSections}
                    report={report}
                    totals={report.totals}
                    showComparison={!!comparisonYearId}
                />
            </div>
        </FinancialReportPageShell>
    );
}
