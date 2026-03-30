import { Badge } from '@/components/ui/badge';
import {
    FinancialReportSection,
    getChangeTextClass,
    type ReportAccountNode,
} from '@/components/reports/financial/FinancialReportSection';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { cn, formatCurrency } from '@/lib/utils';
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

interface IncomeStatementResponse {
    fiscalYears: FiscalYear[];
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
    const [searchParams, setSearchParams] = useSearchParams();
    const urlYearId = searchParams.get('fiscal_year_id');
    const urlComparisonId = searchParams.get('comparison_year_id');

    const { data, isLoading, error } = useQuery<IncomeStatementResponse>({
        queryKey: ['income-statement', urlYearId, urlComparisonId],
        queryFn: async () => {
            const params = new URLSearchParams();
            if (urlYearId) params.append('fiscal_year_id', urlYearId);
            if (urlComparisonId)
                params.append('comparison_year_id', urlComparisonId);
            const response = await axios.get(
                `/api/reports/income-statement?${params.toString()}`,
            );
            return response.data;
        },
    });

    const fiscalYears = data?.fiscalYears || [];
    const selectedYearId = data?.selectedYearId || 0;
    const comparisonYearId = data?.comparisonYearId;
    const report = data?.report || {
        revenues: [],
        expenses: [],
        totals: { revenue: 0, expense: 0, net_income: 0 },
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

    const totalRevenue = report.totals?.revenue || 0;
    const totalExpense = report.totals?.expense || 0;
    const netIncome = report.totals?.net_income || 0;
    const netIncomeComparison = report.totals?.comparison_net_income || 0;
    const netIncomeChange = report.totals?.change_net_income || 0;
    const netIncomeChangePct = report.totals?.change_percentage_net_income || 0;
    const isProfit = netIncome >= 0;

    if (isLoading) {
        return (
            <AppLayout
                breadcrumbs={[
                    { title: 'Reports', href: '#' },
                    {
                        title: 'Income Statement',
                        href: '/reports/income-statement',
                    },
                ]}
            >
                <Helmet>
                    <title>Income Statement</title>
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
                        title: 'Income Statement',
                        href: '/reports/income-statement',
                    },
                ]}
            >
                <Helmet>
                    <title>Income Statement</title>
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
                {
                    title: 'Income Statement',
                    href: '/reports/income-statement',
                },
            ]}
        >
            <Helmet>
                <title>Income Statement</title>
            </Helmet>

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-bold tracking-tight">
                            Income Statement
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
                        title="Revenue"
                        nodes={report.revenues || []}
                        total={report.totals?.revenue || 0}
                        comparisonTotal={report.totals?.comparison_revenue}
                        change={report.totals?.change_revenue}
                        changePercentage={
                            report.totals?.change_percentage_revenue
                        }
                        showComparison={!!comparisonYearId}
                    />

                    <FinancialReportSection
                        title="Expense"
                        nodes={report.expenses || []}
                        total={report.totals?.expense || 0}
                        comparisonTotal={report.totals?.comparison_expense}
                        change={report.totals?.change_expense}
                        changePercentage={
                            report.totals?.change_percentage_expense
                        }
                        showComparison={!!comparisonYearId}
                    />

                    <Card
                        className={cn(
                            'overflow-hidden border-t-4',
                            isProfit
                                ? 'border-emerald-500'
                                : 'border-destructive',
                        )}
                    >
                        <CardHeader className="bg-muted/15">
                            <div className="flex items-start justify-between gap-3">
                                <div className="space-y-1">
                                    <CardTitle className="text-base">
                                        Summary
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Net Income = Total Revenue - Total
                                        Expense.
                                    </CardDescription>
                                </div>
                                <Badge
                                    variant={
                                        isProfit ? 'secondary' : 'destructive'
                                    }
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
                                                {formatCurrency(
                                                    netIncomeChange,
                                                )}{' '}
                                                ({netIncomeChangePct.toFixed(1)}
                                                %)
                                            </span>
                                        </div>
                                    </>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
