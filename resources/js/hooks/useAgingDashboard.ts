import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';

export interface AgingSummary {
    total_outstanding: number;
    current: number;
    '1_30': number;
    '31_60': number;
    '61_90': number;
    over_90: number;
    overdue_amount: number;
    overdue_percentage: number;
    invoice_count: number;
    overdue_count: number;
}

export interface AgingBucket {
    label: string;
    amount: number;
    percentage: number;
}

export interface TopOverdueCustomer {
    customer_id: number;
    customer_name: string;
    outstanding_amount: number;
    overdue_amount: number;
    invoice_count: number;
    oldest_due_date: string | null;
    max_days_overdue: number;
}

export interface TopOverdueSupplier {
    supplier_id: number;
    supplier_name: string;
    outstanding_amount: number;
    overdue_amount: number;
    bill_count: number;
    oldest_due_date: string | null;
    max_days_overdue: number;
}

export interface BranchOption {
    id: number;
    name: string;
}

export interface AgingDashboardData {
    as_of_date: string;
    branches: BranchOption[];
    selected_branch_id: number | null;
    ar_summary: AgingSummary;
    ap_summary: AgingSummary;
    ar_buckets: AgingBucket[];
    ap_buckets: AgingBucket[];
    top_overdue_customers: TopOverdueCustomer[];
    top_overdue_suppliers: TopOverdueSupplier[];
}

interface UseAgingDashboardParams {
    asOfDate?: string | null;
    branchId?: number | null;
}

export function useAgingDashboard(params?: UseAgingDashboardParams) {
    const fetchDashboardData = async (): Promise<AgingDashboardData> => {
        const queryParams = new URLSearchParams();
        if (params?.asOfDate) {
            queryParams.append('as_of_date', params.asOfDate);
        }
        if (params?.branchId) {
            queryParams.append('branch_id', params.branchId.toString());
        }

        const queryString = queryParams.toString();
        const url = queryString
            ? `/api/aging-dashboard?${queryString}`
            : '/api/aging-dashboard';
        const response = await axios.get(url);
        return response.data;
    };

    const query = useQuery({
        queryKey: ['aging-dashboard', params?.asOfDate, params?.branchId],
        queryFn: fetchDashboardData,
        staleTime: 60000,
    });

    return query;
}
