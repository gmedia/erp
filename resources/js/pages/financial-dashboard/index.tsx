import DashboardPageShell from '@/components/common/DashboardPageShell';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { BreadcrumbItem } from '@/types';
import { Info } from 'lucide-react';
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
        <DashboardPageShell
            title="Financial Dashboard"
            heading="Financial Overview"
            description="Monitor key financial metrics, cash flow, and expense trends."
            breadcrumbs={breadcrumbs}
            toolbar={
                <>
                    <div className="flex flex-col gap-1.5" id="branch-filter">
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
                            onComparisonYearChange={handleComparisonYearChange}
                        />
                    )}
                </>
            }
            isLoading={isLoading}
            isError={isError}
            error={error}
            errorMessage="Failed to fetch financial dashboard data from the server. Please try refreshing."
            refetch={refetch}
        >
            {showSegmentBanner && (
                <Alert className="border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                    <Info className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                    <AlertTitle className="font-semibold">
                        Segment view — Branch P&L
                    </AlertTitle>
                    <AlertDescription className="mt-1 text-sm">
                        Revenue, Expenses, and Net Income reflect this
                        branch&apos;s segment only and exclude company-wide /
                        unallocated entries (period-closing, depreciation).
                        Total Assets, Liabilities, Equity, and Cash Balance
                        remain company-wide.
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
        </DashboardPageShell>
    );
}
