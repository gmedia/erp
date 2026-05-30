import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';

export interface KpiItem {
    value: number;
    change: number;
    comparison_value: number;
}

export interface CashFlowSummary {
    inflow: number;
    outflow: number;
    net: number;
}

export interface ExpenseBreakdownItem {
    name: string;
    value: number;
}

export interface FiscalYear {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: string;
}

export interface FinancialDashboardData {
    fiscal_years: FiscalYear[];
    selected_year_id: number | null;
    comparison_year_id: number | null;
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
}

interface UseFinancialDashboardParams {
    fiscalYearId?: number | null;
    comparisonYearId?: number | null;
}

export function useFinancialDashboard(
    params?: UseFinancialDashboardParams,
) {
    const fetchDashboardData = async (): Promise<FinancialDashboardData> => {
        const queryParams = new URLSearchParams();
        if (params?.fiscalYearId) {
            queryParams.append('fiscal_year_id', params.fiscalYearId.toString());
        }
        if (params?.comparisonYearId) {
            queryParams.append('comparison_year_id', params.comparisonYearId.toString());
        }

        const url = `/api/financial-dashboard${queryParams.toString() ? `?${queryParams.toString()}` : ''}`;
        const response = await axios.get(url);
        return response.data;
    };

    const query = useQuery({
        queryKey: ['financial-dashboard', params?.fiscalYearId, params?.comparisonYearId],
        queryFn: fetchDashboardData,
        staleTime: 60000,
    });

    return query;
}
