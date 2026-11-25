import DashboardPageShell from '@/components/common/DashboardPageShell';
import { PageBanner } from '@/components/common/PageBanner';
import { BreadcrumbItem } from '@/types';
import { useEffect, useRef } from 'react';
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
        titleKey: 'menu.financial_dashboard',
        href: '/financial-dashboard',
    },
];

export default function FinancialDashboard() {
    const [searchParams, setSearchParams] = useSearchParams();
    const preferredYearSynced = useRef(false);

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

    useEffect(() => {
        if (preferredYearSynced.current) {
            return;
        }
        if (fiscalYearId !== null) {
            preferredYearSynced.current = true;
            return;
        }
        const preferredId = data?.selected_year_id;
        if (!preferredId) {
            return;
        }
        preferredYearSynced.current = true;
        const next = new URLSearchParams(searchParams);
        next.set('fiscal_year_id', preferredId.toString());
        setSearchParams(next, { replace: true });
    }, [data?.selected_year_id, fiscalYearId, searchParams, setSearchParams]);

    const resolvedYearId = fiscalYearId ?? data?.selected_year_id ?? null;
    const hasComparison =
        comparisonYearId !== null && comparisonYearId !== undefined;

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
            heading="Financial Dashboard"
            headingKey="menu.financial_dashboard"
            description="Monitor key financial metrics, cash flow, and expense trends."
            descriptionKey="page.financial_dashboard.description"
            breadcrumbs={breadcrumbs}
            toolbar={
                <>
                    <div id="branch-filter">
                        <label className="sr-only" htmlFor="branch-filter">
                            Branch
                        </label>
                        <AsyncSelect
                            url="/api/branches"
                            placeholder="All Branches"
                            value={branchId?.toString() ?? ''}
                            onValueChange={handleBranchChange}
                            className="w-full shrink-0 sm:w-[160px]"
                        />
                    </div>
                    {data?.fiscal_years && data.fiscal_years.length > 0 && (
                        <FiscalYearSelector
                            fiscalYears={data.fiscal_years}
                            selectedYearId={resolvedYearId}
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
                <PageBanner tone="info" title="Segment view — Branch P&L">
                    Revenue, Expenses, and Net Income reflect this branch&apos;s
                    segment only and exclude company-wide / unallocated entries
                    (period-closing, depreciation). Total Assets, Liabilities,
                    Equity, and Cash Balance remain company-wide.
                </PageBanner>
            )}

            <div className="space-y-6">
                <SummaryCards
                    data={data?.kpis}
                    isLoading={isLoading}
                    showComparison={hasComparison}
                />

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
