import React from 'react';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useAssetDashboard } from '../../hooks/useAssetDashboard';
import { SummaryCards } from '../../components/asset-dashboard/SummaryCards';
import { StatusDistributionChart } from '../../components/asset-dashboard/StatusDistributionChart';
import { CategoryDistributionChart } from '../../components/asset-dashboard/CategoryDistributionChart';
import { ConditionOverview } from '../../components/asset-dashboard/ConditionOverview';
import { RecentMaintenances } from '../../components/asset-dashboard/RecentMaintenances';
import { WarrantyAlerts } from '../../components/asset-dashboard/WarrantyAlerts';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';

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
            <Head title="Asset Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6 pb-12 w-full max-w-7xl mx-auto">
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">Asset Management Overview</h1>
                        <p className="text-muted-foreground mt-1">
                            Monitor the status, value, and health of your organization's assets.
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
                            <RefreshCw className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
                            Refresh Data
                        </Button>
                    </div>
                </div>

                {isError && (
                    <Alert variant="destructive" className="mb-4">
                        <AlertCircle className="h-4 w-4" />
                        <AlertTitle>Error Loading Dashboard</AlertTitle>
                        <AlertDescription className="mt-2 text-sm max-w-lg">
                            {error?.message || 'Failed to fetch asset dashboard data from the server. Please try refreshing.'}
                        </AlertDescription>
                    </Alert>
                )}

                <div className="space-y-6">
                    {/* Top Row: Summary Cards */}
                    <SummaryCards data={data?.summary} isLoading={isLoading} />

                    {/* Middle Row: Distribution Charts */}
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <StatusDistributionChart data={data?.status_distribution} isLoading={isLoading} />
                        <CategoryDistributionChart data={data?.category_distribution} isLoading={isLoading} />
                    </div>

                    {/* Bottom Row: Condition, Maintenance, and Alerts */}
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <ConditionOverview data={data?.condition_overview} isLoading={isLoading} />
                        <RecentMaintenances data={data?.recent_maintenances} isLoading={isLoading} />
                        <WarrantyAlerts data={data?.warranty_alerts} isLoading={isLoading} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
