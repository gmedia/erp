import { PageHeader } from '@/components/common/PageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';
import { Helmet } from 'react-helmet-async';

export default function VehicleLoanDashboardPage() {
    const { data } = useQuery({
        queryKey: ['vehicle-loans', 'dashboard'],
        queryFn: async () => {
            const response = await axios.get('/api/vehicle-loans?per_page=100');
            return response.data.data as Array<{
                id: number;
                status: string;
                is_overdue?: boolean;
                plate_number: string | null;
            }>;
        },
    });

    const loans = data ?? [];
    const count = (status: string) =>
        loans.filter((loan) => loan.status === status).length;

    const cards = [
        { label: 'Total permintaan', value: loans.length },
        { label: 'Draft', value: count('draft') },
        {
            label: 'Menunggu approval',
            value: count('pending') + count('in_progress'),
        },
        { label: 'Disetujui', value: count('approved') },
        { label: 'Ditolak', value: count('rejected') },
        { label: 'Dipakai', value: count('pickup_done') },
        { label: 'Selesai', value: count('returned') },
        {
            label: 'Terlambat',
            value: loans.filter((loan) => loan.is_overdue).length,
        },
    ];

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Vehicle Loan Dashboard',
                    href: '/vehicle-loans/dashboard',
                },
            ]}
        >
            <Helmet>
                <title>Dashboard Peminjaman Kendaraan </title>
            </Helmet>
            <div className="mx-auto max-w-6xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title="Dashboard Peminjaman Kendaraan"
                    description="Ringkasan status permintaan dan utilisasi kendaraan."
                />
                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    {cards.map((card) => (
                        <Card key={card.label}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    {card.label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold">
                                    {card.value}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
