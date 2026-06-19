import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';

export interface KpiItem {
    value: number;
    change: number;
    comparison_value: number;
    scope: 'branch' | 'company';
}

export interface CashFlowSummary {
    inflow: number;
    outflow: number;
    net: number;
    scope: 'branch' | 'company';
}

export interface ExpenseBreakdownItem {
    name: string;
    value: number;
}

export interface MonthlyTrendItem {
    month: number;
    label: string;
    revenue: number;
    expenses: number;
    net_income: number;
}

export interface FiscalYear {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: string;
}

export interface BranchScope {
    branch_id: number | null;
    segment_scope: 'branch' | 'company';
    excludes_unallocated: boolean;
}

export interface FinancialDashboardData {
    fiscal_years: FiscalYear[];
    selected_year_id: number | null;
    comparison_year_id: number | null;
    selected_branch_id: number | null;
    branch_scope: BranchScope;
    kpis: {
        revenue: KpiItem;
        expenses: KpiItem;
        net_income: KpiItem;
        total_assets: KpiItem;
        total_liabilities: KpiItem;
        equity: KpiItem;
        cash_balance: KpiItem;
    };
    cash_flow_summary: CashFlowSummary;
    expense_breakdown: ExpenseBreakdownItem[];
    monthly_trends: MonthlyTrendItem[];
}

interface UseFinancialDashboardParams {
    fiscalYearId?: number | null;
    comparisonYearId?: number | null;
    branchId?: number | null;
}

export function useFinancialDashboard(params?: UseFinancialDashboardParams) {
    const fetchDashboardData = async (): Promise<FinancialDashboardData> => {
        const queryParams = new URLSearchParams();
        if (params?.fiscalYearId) {
            queryParams.append(
                'fiscal_year_id',
                params.fiscalYearId.toString(),
            );
        }
        if (params?.comparisonYearId) {
            queryParams.append(
                'comparison_year_id',
                params.comparisonYearId.toString(),
            );
        }
        if (params?.branchId) {
            queryParams.append('branch_id', params.branchId.toString());
        }

        const queryString = queryParams.toString();
        const url = queryString
            ? `/api/financial-dashboard?${queryString}`
            : '/api/financial-dashboard';
        const response = await axios.get(url);
        return response.data;
    };

    const query = useQuery({
        queryKey: [
            'financial-dashboard',
            params?.fiscalYearId,
            params?.comparisonYearId,
            params?.branchId,
        ],
        queryFn: fetchDashboardData,
        staleTime: 60000,
    });

    return query;
}
