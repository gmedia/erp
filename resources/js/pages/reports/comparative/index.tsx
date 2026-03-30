import { Badge } from '@/components/ui/badge';
import {
    FinancialReportSection,
    type ReportAccountNode,
} from '@/components/reports/financial/FinancialReportSection';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';
import { Helmet } from 'react-helmet-async';
import { useSearchParams } from 'react-router-dom';

interface FiscalYear {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: string;
}

interface ComparativeReportResponse {
    fiscalYears: FiscalYear[];
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
    const [searchParams, setSearchParams] = useSearchParams();
    const urlYearId = searchParams.get('fiscal_year_id');
    const urlComparisonId = searchParams.get('comparison_year_id');

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

    const handleYearChange = (value: string) => {
        const params: Record<string, string> = { fiscal_year_id: value };
        if (comparisonYearId)
            params.comparison_year_id = String(comparisonYearId);
        setSearchParams(params);
    };

    const handleComparisonChange = (value: string) => {
        const params: Record<string, string> = {
            fiscal_year_id: String(selectedYearId),
        };
        if (value !== 'none') params.comparison_year_id = value;
        setSearchParams(params);
    };

    if (isLoading) {
        return (
            <AppLayout
                breadcrumbs={[
                    { title: 'Reports', href: '#' },
                    {
                        title: 'Comparative Report',
                        href: '/reports/comparative',
                    },
                ]}
            >
                <Helmet>
                    <title>Comparative Report</title>
                </Helmet>
                <div className="flex h-full items-center justify-center p-4">
                    Loading report...
                </div>
            </AppLayout>
        );
    }

    if (error) {
        return (
            <AppLayout
                breadcrumbs={[
                    { title: 'Reports', href: '#' },
                    {
                        title: 'Comparative Report',
                        href: '/reports/comparative',
                    },
                ]}
            >
                <Helmet>
                    <title>Comparative Report</title>
                </Helmet>
                <div className="flex h-full items-center justify-center p-4 text-destructive">
                    Error loading report.
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Reports', href: '#' },
                { title: 'Comparative Report', href: '/reports/comparative' },
            ]}
        >
            <Helmet>
                <title>Comparative Report</title>
            </Helmet>

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-bold tracking-tight">
                            Comparative Report
                        </h1>
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
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:gap-4">
                        <div className="w-full sm:w-[220px]">
                            <Select
                                value={String(selectedYearId)}
                                onValueChange={handleYearChange}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Fiscal Year" />
                                </SelectTrigger>
                                <SelectContent>
                                    {fiscalYears.map((fy) => (
                                        <SelectItem
                                            key={fy.id}
                                            value={String(fy.id)}
                                        >
                                            {fy.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="w-full sm:w-[220px]">
                            <Select
                                value={
                                    comparisonYearId
                                        ? String(comparisonYearId)
                                        : 'none'
                                }
                                onValueChange={handleComparisonChange}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Compare With..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">None</SelectItem>
                                    {fiscalYears
                                        .filter(
                                            (fy) => fy.id !== selectedYearId,
                                        )
                                        .map((fy) => (
                                            <SelectItem
                                                key={fy.id}
                                                value={String(fy.id)}
                                            >
                                                {fy.name}
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6">
                    <FinancialReportSection
                        title="Assets"
                        nodes={report.assets || []}
                        total={report.totals?.assets || 0}
                        comparisonTotal={report.totals?.comparison_assets}
                        change={report.totals?.change_assets}
                        changePercentage={
                            report.totals?.change_percentage_assets
                        }
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
                        changePercentage={
                            report.totals?.change_percentage_equity
                        }
                        showComparison={!!comparisonYearId}
                    />
                    <FinancialReportSection
                        title="Revenue"
                        nodes={report.revenues || []}
                        total={report.totals?.revenues || 0}
                        comparisonTotal={report.totals?.comparison_revenues}
                        change={report.totals?.change_revenues}
                        changePercentage={
                            report.totals?.change_percentage_revenues
                        }
                        showComparison={!!comparisonYearId}
                    />
                    <FinancialReportSection
                        title="Expense"
                        nodes={report.expenses || []}
                        total={report.totals?.expenses || 0}
                        comparisonTotal={report.totals?.comparison_expenses}
                        change={report.totals?.change_expenses}
                        changePercentage={
                            report.totals?.change_percentage_expenses
                        }
                        showComparison={!!comparisonYearId}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
