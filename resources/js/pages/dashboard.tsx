import DashboardPageShell from '@/components/common/DashboardPageShell';
import { KpiCard } from '@/components/common/KpiCard';
import { Card, CardContent } from '@/components/ui/card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import axios from '@/lib/axios';
import { type BreadcrumbItem } from '@/types';
import { formatNumberByRegionalSettings } from '@/utils/number-format';
import { useQuery } from '@tanstack/react-query';
import { Boxes, Building2, Users, Warehouse } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

type Totals = {
    customers: number;
    employees: number;
    suppliers: number;
    assets: number;
};

async function fetchDashboardTotals(): Promise<Totals> {
    const res = await axios.get('/api/dashboard');
    return res.data.data.totals as Totals;
}

export default function Dashboard() {
    const { data, isLoading, isError, error, refetch } = useQuery({
        queryKey: ['home-dashboard'],
        queryFn: fetchDashboardTotals,
        staleTime: 60_000,
    });

    const totals: Totals = data ?? {
        customers: 0,
        employees: 0,
        suppliers: 0,
        assets: 0,
    };

    const cards = [
        {
            title: 'Total Customers',
            icon: Users,
            borderColor: 'border-l-blue-500',
            iconColor: 'text-blue-500',
            value: totals.customers,
        },
        {
            title: 'Total Employees',
            icon: Building2,
            borderColor: 'border-l-emerald-500',
            iconColor: 'text-emerald-500',
            value: totals.employees,
        },
        {
            title: 'Total Suppliers',
            icon: Warehouse,
            borderColor: 'border-l-amber-500',
            iconColor: 'text-amber-500',
            value: totals.suppliers,
        },
        {
            title: 'Total Assets',
            icon: Boxes,
            borderColor: 'border-l-indigo-500',
            iconColor: 'text-indigo-500',
            value: totals.assets,
        },
    ] as const;

    return (
        <DashboardPageShell
            title="Dashboard"
            heading="Dashboard"
            description="At-a-glance entity counts across customers, people, suppliers, and assets."
            breadcrumbs={breadcrumbs}
            isLoading={isLoading}
            isError={isError}
            error={error instanceof Error ? error : null}
            errorMessage="Failed to fetch dashboard totals. Please try refreshing."
            refetch={() => {
                void refetch();
            }}
        >
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {isLoading
                    ? (['c1', 'c2', 'c3', 'c4'] as const).map((key) => (
                          <div
                              key={key}
                              className="animate-pulse rounded-lg border bg-card p-6 shadow-sm"
                          >
                              <div className="mb-2 h-4 w-28 rounded bg-muted" />
                              <div className="h-8 w-20 rounded bg-muted" />
                          </div>
                      ))
                    : cards.map((card) => (
                          <KpiCard
                              key={card.title}
                              title={card.title}
                              icon={card.icon}
                              value={card.value}
                              formattedValue={formatNumberByRegionalSettings(
                                  card.value,
                              )}
                              borderColor={card.borderColor}
                              iconColor={card.iconColor}
                          />
                      ))}
            </div>

            <Card className="relative min-h-[40vh] overflow-hidden border-dashed">
                <CardContent className="relative flex min-h-[40vh] items-center justify-center p-0">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/15 dark:stroke-neutral-100/15" />
                    <p className="relative z-10 max-w-sm px-6 text-center text-sm text-muted-foreground">
                        More operational widgets will appear here. Use Financial
                        Overview and module dashboards for detailed KPIs.
                    </p>
                </CardContent>
            </Card>
        </DashboardPageShell>
    );
}
