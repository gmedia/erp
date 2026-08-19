import DashboardPageShell from '@/components/common/DashboardPageShell';
import { KpiCard } from '@/components/common/KpiCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import axios from '@/lib/axios';
import { type BreadcrumbItem } from '@/types';
import { formatNumberByRegionalSettings } from '@/utils/number-format';
import { useQuery } from '@tanstack/react-query';
import {
    Boxes,
    Building2,
    ClipboardCheck,
    PackageSearch,
    ShoppingCart,
    Users,
    Warehouse,
} from 'lucide-react';
import { Link } from 'react-router-dom';

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

    const shortcuts = [
        {
            title: 'My Approvals',
            href: '/my-approvals',
            description: 'Review pending requests',
            icon: ClipboardCheck,
        },
        {
            title: 'Purchase Orders',
            href: '/purchase-orders',
            description: 'Open the PO list',
            icon: ShoppingCart,
        },
        {
            title: 'Stock Monitor',
            href: '/stock-monitor',
            description: 'On-hand quantity by warehouse',
            icon: PackageSearch,
        },
    ] as const;

    const composition = [
        {
            key: 'customers',
            label: 'Customers',
            value: totals.customers,
            barClass: 'bg-blue-500',
        },
        {
            key: 'employees',
            label: 'Employees',
            value: totals.employees,
            barClass: 'bg-emerald-500',
        },
        {
            key: 'suppliers',
            label: 'Suppliers',
            value: totals.suppliers,
            barClass: 'bg-amber-500',
        },
        {
            key: 'assets',
            label: 'Assets',
            value: totals.assets,
            barClass: 'bg-indigo-500',
        },
    ] as const;

    const maxComposition = Math.max(...composition.map((row) => row.value), 1);

    return (
        <DashboardPageShell
            title="Dashboard"
            heading="Dashboard"
            description="At-a-glance entity counts, operational shortcuts, and master-data mix. Financial KPIs stay on Financial Overview."
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

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card data-testid="dashboard-shortcuts">
                    <CardHeader>
                        <CardTitle>Shortcuts</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-2">
                        {shortcuts.map((item) => (
                            <Link
                                key={item.href}
                                to={item.href}
                                className="flex items-start gap-3 rounded-lg border p-3 text-left transition-colors hover:bg-accent"
                            >
                                <item.icon className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                <span className="min-w-0">
                                    <span className="block text-sm font-medium">
                                        {item.title}
                                    </span>
                                    <span className="block text-xs text-muted-foreground">
                                        {item.description}
                                    </span>
                                </span>
                            </Link>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Master data mix</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {composition.map((row) => (
                            <div key={row.key} className="space-y-1">
                                <div className="flex items-center justify-between text-sm">
                                    <span>{row.label}</span>
                                    <span className="text-muted-foreground tabular-nums">
                                        {formatNumberByRegionalSettings(
                                            row.value,
                                        )}
                                    </span>
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-muted">
                                    <div
                                        className={`h-full rounded-full ${row.barClass}`}
                                        style={{
                                            width: `${(row.value / maxComposition) * 100}%`,
                                        }}
                                    />
                                </div>
                            </div>
                        ))}
                        <p className="text-xs text-muted-foreground">
                            Operational counts only. Financial KPIs stay on
                            Financial Overview.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </DashboardPageShell>
    );
}
