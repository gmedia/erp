import DashboardPageShell from '@/components/common/DashboardPageShell';
import { BreadcrumbItem } from '@/types';
import { CategoryDistributionChart } from '../../components/asset-dashboard/CategoryDistributionChart';
import { ConditionOverview } from '../../components/asset-dashboard/ConditionOverview';
import { RecentMaintenances } from '../../components/asset-dashboard/RecentMaintenances';
import { StatusDistributionChart } from '../../components/asset-dashboard/StatusDistributionChart';
import { SummaryCards } from '../../components/asset-dashboard/SummaryCards';
import { WarrantyAlerts } from '../../components/asset-dashboard/WarrantyAlerts';
import { useAssetDashboard } from '../../hooks/useAssetDashboard';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Asset Dashboard',
        href: '/asset-dashboard',
    },
];

export default function AssetDashboard() {
    const { data, isLoading, isError, error, refetch } = useAssetDashboard();

    return (
        <DashboardPageShell
            title="Asset Dashboard"
            heading="Asset Management Overview"
            description="Monitor the status, value, and health of your organization's assets."
            breadcrumbs={breadcrumbs}
            isLoading={isLoading}
            isError={isError}
            error={error}
            errorMessage="Failed to fetch asset dashboard data from the server. Please try refreshing."
            refetch={refetch}
        >
            <div className="space-y-6">
                {/* Top Row: Summary Cards */}
                <SummaryCards data={data?.summary} isLoading={isLoading} />

                {/* Middle Row: Distribution Charts */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <StatusDistributionChart
                        data={data?.status_distribution}
                        isLoading={isLoading}
                    />
                    <CategoryDistributionChart
                        data={data?.category_distribution}
                        isLoading={isLoading}
                    />
                </div>

                {/* Bottom Row: Condition, Maintenance, and Alerts */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <ConditionOverview
                        data={data?.condition_overview}
                        isLoading={isLoading}
                    />
                    <RecentMaintenances
                        data={data?.recent_maintenances}
                        isLoading={isLoading}
                    />
                    <WarrantyAlerts
                        data={data?.warranty_alerts}
                        isLoading={isLoading}
                    />
                </div>
            </div>
        </DashboardPageShell>
    );
}
