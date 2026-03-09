import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { AlertCircle, RefreshCw } from 'lucide-react';
import { Helmet } from 'react-helmet-async';
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
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>Asset Dashboard</title>
            </Helmet>
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 pb-12 md:p-6">
                <div className="flex flex-col items-start justify-between md:flex-row md:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Asset Management Overview
                        </h1>
                        <p className="mt-1 text-muted-foreground">
                            Monitor the status, value, and health of your
                            organization's assets.
                        </p>
                    </div>
                    <div className="mt-4 md:mt-0">
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
                                'Failed to fetch asset dashboard data from the server. Please try refreshing.'}
                        </AlertDescription>
                    </Alert>
                )}

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
            </div>
        </AppLayout>
    );
}
