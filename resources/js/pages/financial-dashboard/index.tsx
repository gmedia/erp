import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { AlertCircle, Info, RefreshCw } from 'lucide-react';
import { Helmet } from 'react-helmet-async';
import { useSearchParams } from 'react-router-dom';
import { AsyncSelect } from '../../components/common/AsyncSelect';
import { CashFlowSummary } from '../../components/financial-dashboard/CashFlowSummary';
import { ExpenseBreakdown } from '../../components/financial-dashboard/ExpenseBreakdown';
import { FiscalYearSelector } from '../../components/financial-dashboard/FiscalYearSelector';
import { MonthlyTrendChart } from '../../components/financial-dashboard/MonthlyTrendChart';
import { SummaryCards } from '../../components/financial-dashboard/SummaryCards';
import { useFinancialDashboard } from '../../hooks/useFinancialDashboard';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Financial Dashboard',
        href: '/financial-dashboard',
    },
];

export default function FinancialDashboard() {
    const [searchParams, setSearchParams] = useSearchParams();

    const fiscalYearId = searchParams.get('fiscal_year_id')
        ? Number(searchParams.get('fiscal_year_id'))
        : null;
    const comparisonYearId = searchParams.get('comparison_year_id')
        ? Number(searchParams.get('comparison_year_id'))
        : null;
    const branchId = searchParams.get('branch_id')
        ? Number(searchParams.get('branch_id'))
        : null;

    const { data, isLoading, isError, error, refetch } = useFinancialDashboard({
        fiscalYearId,
        comparisonYearId,
        branchId,
    });

    const handleYearChange = (yearId: number | null) => {
        const newParams = new URLSearchParams(searchParams);
        if (yearId) {
            newParams.set('fiscal_year_id', yearId.toString());
        } else {
            newParams.delete('fiscal_year_id');
        }
        setSearchParams(newParams);
    };

    const handleComparisonYearChange = (yearId: number | null) => {
        const newParams = new URLSearchParams(searchParams);
        if (yearId) {
            newParams.set('comparison_year_id', yearId.toString());
        } else {
            newParams.delete('comparison_year_id');
        }
        setSearchParams(newParams);
    };

    const handleBranchChange = (value: string) => {
        const newParams = new URLSearchParams(searchParams);
        if (value) {
            newParams.set('branch_id', value);
        } else {
            newParams.delete('branch_id');
        }
        setSearchParams(newParams);
    };

    const showSegmentBanner = data?.branch_scope?.excludes_unallocated === true;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>Financial Dashboard</title>
            </Helmet>
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 pb-12 md:p-6">
                <div className="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Financial Overview
                        </h1>
                        <p className="mt-1 text-muted-foreground">
                            Monitor key financial metrics, cash flow, and
                            expense trends.
                        </p>
                    </div>
                    <div className="flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                        <div
                            className="flex flex-col gap-1.5"
                            id="branch-filter"
                        >
                            <label
                                className="text-sm font-medium text-muted-foreground"
                                htmlFor="branch-filter"
                            >
                                Branch
                            </label>
                            <AsyncSelect
                                url="/api/branches"
                                placeholder="All Branches"
                                value={branchId?.toString() ?? ''}
                                onValueChange={handleBranchChange}
                                className="w-[180px]"
                            />
                        </div>
                        {data?.fiscal_years && data.fiscal_years.length > 0 && (
                            <FiscalYearSelector
                                fiscalYears={data.fiscal_years}
                                selectedYearId={fiscalYearId}
                                comparisonYearId={comparisonYearId}
                                onYearChange={handleYearChange}
                                onComparisonYearChange={
                                    handleComparisonYearChange
                                }
                            />
                        )}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => refetch()}
                            disabled={isLoading}
                            className="flex items-center gap-2"
                        >
                            <RefreshCw
                                className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`}
                            />
                            Refresh Data
                        </Button>
                    </div>
                </div>

                {isError && (
                    <Alert variant="destructive" className="mb-4">
                        <AlertCircle className="h-4 w-4" />
                        <AlertTitle>Error Loading Dashboard</AlertTitle>
                        <AlertDescription className="mt-2 max-w-lg text-sm">
                            {error?.message ||
                                'Failed to fetch financial dashboard data from the server. Please try refreshing.'}
                        </AlertDescription>
                    </Alert>
                )}

                {showSegmentBanner && (
                    <Alert className="border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                        <Info className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        <AlertTitle className="font-semibold">
                            Segment view — Branch P&L
                        </AlertTitle>
                        <AlertDescription className="mt-1 text-sm">
                            Revenue, Expenses, and Net Income reflect this
                            branch&apos;s segment only and exclude company-wide
                            / unallocated entries (period-closing,
                            depreciation). Total Assets, Liabilities, Equity,
                            and Cash Balance remain company-wide.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="space-y-6">
                    <SummaryCards data={data?.kpis} isLoading={isLoading} />

                    <MonthlyTrendChart
                        data={data?.monthly_trends}
                        isLoading={isLoading}
                    />

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <CashFlowSummary
                            data={data?.cash_flow_summary}
                            isLoading={isLoading}
                        />
                        <ExpenseBreakdown
                            data={data?.expense_breakdown}
                            isLoading={isLoading}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
