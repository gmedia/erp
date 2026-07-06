import DashboardPageShell from '@/components/common/DashboardPageShell';
import { BreadcrumbItem } from '@/types';
import { useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import { AgingBucketChart } from '../../components/aging-dashboard/AgingBucketChart';
import { AgingFilters } from '../../components/aging-dashboard/AgingFilters';
import { AgingSummaryCards } from '../../components/aging-dashboard/AgingSummaryCards';
import { TopOverdueCustomers } from '../../components/aging-dashboard/TopOverdueCustomers';
import { TopOverdueSuppliers } from '../../components/aging-dashboard/TopOverdueSuppliers';
import {
    type AgingBucket,
    type TopOverdueCustomer,
    type TopOverdueSupplier,
    useAgingDashboard,
} from '../../hooks/useAgingDashboard';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Aging Dashboard',
        href: '/aging-dashboard',
    },
];

const EMPTY_BUCKETS: AgingBucket[] = [];
const EMPTY_CUSTOMERS: TopOverdueCustomer[] = [];
const EMPTY_SUPPLIERS: TopOverdueSupplier[] = [];

export default function AgingDashboard() {
    const [searchParams, setSearchParams] = useSearchParams();

    const today = new Date().toISOString().slice(0, 10);
    const asOfDate: string = searchParams.get('as_of_date') || today;
    const branchId = searchParams.get('branch_id')
        ? Number(searchParams.get('branch_id'))
        : null;

    const { data, isLoading, isError, error, refetch } = useAgingDashboard({
        asOfDate,
        branchId,
    });

    const handleAsOfDateChange = useCallback(
        (date: string) => {
            setSearchParams((prev) => {
                const newParams = new URLSearchParams(prev);
                if (date) {
                    newParams.set('as_of_date', date);
                } else {
                    newParams.delete('as_of_date');
                }
                return newParams;
            });
        },
        [setSearchParams],
    );

    const handleBranchChange = useCallback(
        (newBranchId: number | null) => {
            setSearchParams((prev) => {
                const newParams = new URLSearchParams(prev);
                if (newBranchId) {
                    newParams.set('branch_id', newBranchId.toString());
                } else {
                    newParams.delete('branch_id');
                }
                return newParams;
            });
        },
        [setSearchParams],
    );

    return (
        <DashboardPageShell
            title="Aging Dashboard"
            heading="Accounts Aging Overview"
            description="Monitor outstanding receivables and payables bucketed by overdue age."
            breadcrumbs={breadcrumbs}
            toolbar={
                data?.branches ? (
                    <AgingFilters
                        asOfDate={asOfDate}
                        branchId={branchId}
                        branches={data.branches}
                        onAsOfDateChange={handleAsOfDateChange}
                        onBranchChange={handleBranchChange}
                    />
                ) : undefined
            }
            isLoading={isLoading}
            isError={isError}
            error={error}
            errorMessage="Failed to fetch aging dashboard data from the server. Please try refreshing."
            refetch={refetch}
        >
            <div className="space-y-6">
                <AgingSummaryCards
                    arSummary={data?.ar_summary}
                    apSummary={data?.ap_summary}
                    isLoading={isLoading}
                />

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <AgingBucketChart
                        title="Receivables (AR)"
                        buckets={data?.ar_buckets ?? EMPTY_BUCKETS}
                        totalOutstanding={
                            data?.ar_summary?.total_outstanding ?? 0
                        }
                        isLoading={isLoading}
                        accentColor="emerald"
                    />
                    <AgingBucketChart
                        title="Payables (AP)"
                        buckets={data?.ap_buckets ?? EMPTY_BUCKETS}
                        totalOutstanding={
                            data?.ap_summary?.total_outstanding ?? 0
                        }
                        isLoading={isLoading}
                        accentColor="rose"
                    />
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <TopOverdueCustomers
                        customers={
                            data?.top_overdue_customers ?? EMPTY_CUSTOMERS
                        }
                        isLoading={isLoading}
                    />
                    <TopOverdueSuppliers
                        suppliers={
                            data?.top_overdue_suppliers ?? EMPTY_SUPPLIERS
                        }
                        isLoading={isLoading}
                    />
                </div>
            </div>
        </DashboardPageShell>
    );
}
