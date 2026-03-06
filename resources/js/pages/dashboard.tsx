import { Helmet } from 'react-helmet-async';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { useEffect, useState } from 'react';
import axios from '@/lib/axios';

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

export default function Dashboard() {
    const [totals, setTotals] = useState<Totals | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        axios.get('/api/v1/dashboard')
            .then((res) => {
                setTotals(res.data.data.totals);
            })
            .catch(() => {
                setTotals({ customers: 0, employees: 0, suppliers: 0, assets: 0 });
            })
            .finally(() => setLoading(false));
    }, []);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet><title>Dashboard</title></Helmet>
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Customer
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold tabular-nums">
                            {loading ? '—' : totals?.customers.toLocaleString()}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Employee
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold tabular-nums">
                            {loading ? '—' : totals?.employees.toLocaleString()}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Supplier
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold tabular-nums">
                            {loading ? '—' : totals?.suppliers.toLocaleString()}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Asset
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold tabular-nums">
                            {loading ? '—' : totals?.assets.toLocaleString()}
                        </CardContent>
                    </Card>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </AppLayout>
    );
}
