import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

export interface AssetDashboardSummary {
    total_assets: number;
    total_purchase_cost: number;
    total_book_value: number;
    total_accumulated_depreciation: number;
}

export interface StatusDistributionItem {
    id: string;
    name: string;
    count: number;
    color: string;
}

export interface CategoryDistributionItem {
    name: string;
    count: number;
}

export interface ConditionOverviewItem {
    id: string;
    name: string;
    count: number;
    color: string;
}

export interface RecentMaintenanceItem {
    id: number;
    asset_name: string;
    asset_code: string;
    maintenance_type: string;
    status: string;
    scheduled_at: string | null;
}

export interface WarrantyAlertItem {
    id: number;
    asset_code: string;
    name: string;
    warranty_end_date: string;
    days_remaining: number;
}

export interface AssetDashboardData {
    summary: AssetDashboardSummary;
    status_distribution: StatusDistributionItem[];
    category_distribution: CategoryDistributionItem[];
    condition_overview: ConditionOverviewItem[];
    recent_maintenances: RecentMaintenanceItem[];
    warranty_alerts: WarrantyAlertItem[];
}

export function useAssetDashboard() {
    const fetchDashboardData = async (): Promise<AssetDashboardData> => {
        const response = await axios.get('/api/asset-dashboard/data');
        return response.data;
    };

    const query = useQuery({
        queryKey: ['asset-dashboard'],
        queryFn: fetchDashboardData,
        staleTime: 60000,
    });

    return query;
}
