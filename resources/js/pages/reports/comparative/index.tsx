import {
    FinancialReportPageShell,
    useComparisonReportSearchParams,
    type FinancialReportFiscalYear,
} from '@/components/reports/financial/FinancialReportPageShell';
import {
    FinancialReportSection,
    type ReportAccountNode,
} from '@/components/reports/financial/FinancialReportSection';
import { Badge } from '@/components/ui/badge';
import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';

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

export default function ComparativeReport() {
    const {
        urlYearId,
        urlComparisonId,
        handleYearChange,
        handleComparisonChange,
    } = useComparisonReportSearchParams();

    const { data, isLoading, error } = useQuery<ComparativeReportResponse>({
        queryKey: ['comparative-report', urlYearId, urlComparisonId],
        queryFn: async () => {
            const params = new URLSearchParams();
            if (urlYearId) params.append('fiscal_year_id', urlYearId);
            if (urlComparisonId)
                params.append('comparison_year_id', urlComparisonId);
            const response = await axios.get(
                `/api/reports/comparative?${params.toString()}`,
            );
            return response.data;
        },
    });

    const fiscalYears = data?.fiscalYears || [];
    const selectedYearId = data?.selectedYearId || 0;
    const comparisonYearId = data?.comparisonYearId;
    const report = data?.report || {
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

    const selectedFiscalYear = fiscalYears.find(
        (fy) => fy.id === selectedYearId,
    );
    const selectedComparisonFiscalYear = comparisonYearId
        ? fiscalYears.find((fy) => fy.id === comparisonYearId)
        : undefined;

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
                <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                    {selectedFiscalYear && (
                        <span>
                            {selectedFiscalYear.name} •{' '}
                            {selectedFiscalYear.status}
                        </span>
                    )}
                    <Badge variant="outline">
                        {selectedComparisonFiscalYear
                            ? `Compare: ${selectedComparisonFiscalYear.name}`
                            : 'Compare: None'}
                    </Badge>
                </div>
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
                    changePercentage={report.totals?.change_percentage_equity}
                    showComparison={!!comparisonYearId}
                />
                <FinancialReportSection
                    title="Revenue"
                    nodes={report.revenues || []}
                    total={report.totals?.revenues || 0}
                    comparisonTotal={report.totals?.comparison_revenues}
                    change={report.totals?.change_revenues}
                    changePercentage={report.totals?.change_percentage_revenues}
                    showComparison={!!comparisonYearId}
                />
                <FinancialReportSection
                    title="Expense"
                    nodes={report.expenses || []}
                    total={report.totals?.expenses || 0}
                    comparisonTotal={report.totals?.comparison_expenses}
                    change={report.totals?.change_expenses}
                    changePercentage={report.totals?.change_percentage_expenses}
                    showComparison={!!comparisonYearId}
                />
            </div>
        </FinancialReportPageShell>
    );
}
