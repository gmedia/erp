import { PageHeader } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { VehicleLoanStatusBadge } from '@/components/vehicle-loans/VehicleLoanStatusBadge';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import { useQuery } from '@tanstack/react-query';
import {
    AlertTriangle,
    Ban,
    CarFront,
    CheckCircle2,
    Clock3,
    FileText,
    KeyRound,
    XCircle,
} from 'lucide-react';
import { Helmet } from 'react-helmet-async';
import { Link } from 'react-router-dom';

interface DashboardLoan {
    id: number;
    code: string;
    status: string;
    is_overdue?: boolean;
    requester_name: string | null;
    plate_number: string | null;
    start_at: string | null;
}

export default function VehicleLoanDashboardPage() {
    const { data, dataUpdatedAt, isLoading } = useQuery({
        queryKey: ['vehicle-loans', 'dashboard'],
        queryFn: async () => {
            const response = await axios.get('/api/vehicle-loans?per_page=100');
            return response.data.data as DashboardLoan[];
        },
    });

    const loans = data ?? [];
    const count = (status: string) =>
        loans.filter((loan) => loan.status === status).length;
    const overdue = loans.filter((loan) => loan.is_overdue).length;

    const statusCards = [
        {
            label: 'Total permintaan',
            value: loans.length,
            icon: FileText,
            tone: 'text-slate-600 bg-slate-100',
        },
        {
            label: 'Draft',
            value: count('draft'),
            icon: FileText,
            tone: 'text-slate-600 bg-slate-100',
        },
        {
            label: 'Menunggu approval',
            value: count('pending') + count('in_progress'),
            icon: Clock3,
            tone: 'text-amber-700 bg-amber-50',
        },
        {
            label: 'Disetujui',
            value: count('approved'),
            icon: CheckCircle2,
            tone: 'text-emerald-700 bg-emerald-50',
        },
        {
            label: 'Ditolak',
            value: count('rejected'),
            icon: XCircle,
            tone: 'text-rose-700 bg-rose-50',
        },
        {
            label: 'Kendaraan dipakai',
            value: count('pickup_done'),
            icon: KeyRound,
            tone: 'text-sky-700 bg-sky-50',
        },
        {
            label: 'Selesai',
            value: count('returned'),
            icon: CarFront,
            tone: 'text-emerald-700 bg-emerald-50',
        },
        {
            label: 'Dibatalkan',
            value: count('cancelled'),
            icon: Ban,
            tone: 'text-slate-600 bg-slate-100',
        },
    ];

    const recentLoans = loans.slice(0, 5);

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Dashboard Peminjaman',
                    href: '/vehicle-loans/dashboard',
                },
            ]}
        >
            <Helmet>
                <title>Dashboard Peminjaman Kendaraan</title>
            </Helmet>
            <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Dashboard Peminjaman Kendaraan"
                    description="Ringkasan status permintaan, keterlambatan, dan aktivitas terbaru."
                />

                <section className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-semibold">
                            Status Permintaan
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            Diperbarui{' '}
                            {new Date(dataUpdatedAt).toLocaleTimeString(
                                'id-ID',
                                { hour: '2-digit', minute: '2-digit' },
                            )}
                        </p>
                    </div>
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        {statusCards.map((card) => {
                            const Icon = card.icon;
                            return (
                                <Card key={card.label}>
                                    <CardContent className="flex items-center gap-3 pt-5">
                                        <span
                                            className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${card.tone}`}
                                        >
                                            <Icon className="size-5" />
                                        </span>
                                        <div className="min-w-0">
                                            <p className="truncate text-xs text-muted-foreground">
                                                {card.label}
                                            </p>
                                            <p className="text-2xl font-bold">
                                                {isLoading ? '-' : card.value}
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                </section>

                <section className="grid gap-3 md:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-sm">
                                <AlertTriangle className="size-4 text-amber-600" />
                                Terlambat dikembalikan
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{overdue}</p>
                            <p className="text-xs text-muted-foreground">
                                Kendaraan melewati jadwal pengembalian.
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-sm">
                                <CarFront className="size-4 text-sky-600" />
                                Sedang dipakai
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">
                                {count('pickup_done')}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Kunci sudah diserahkan dan belum dikembalikan.
                            </p>
                        </CardContent>
                    </Card>
                </section>

                <section className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-semibold">
                            Permintaan Terbaru
                        </h2>
                        <Button variant="outline" size="sm" asChild>
                            <Link to="/vehicle-loans/security">
                                Lihat Antrean Security
                            </Link>
                        </Button>
                    </div>
                    <Card>
                        <CardContent className="divide-y py-0">
                            {recentLoans.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    Belum ada permintaan peminjaman.
                                </p>
                            ) : (
                                recentLoans.map((loan) => (
                                    <div
                                        key={loan.id}
                                        className="flex items-center gap-3 py-3"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <Link
                                                to={`/vehicle-loans/${loan.id}`}
                                                className="text-sm font-medium hover:underline"
                                            >
                                                {loan.code}
                                            </Link>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {loan.requester_name ?? '-'} ·{' '}
                                                {loan.plate_number ??
                                                    'unit belum ditentukan'}
                                            </p>
                                        </div>
                                        <p className="hidden text-xs text-muted-foreground sm:block">
                                            {formatDateTimeByRegionalSettings(
                                                loan.start_at,
                                            )}
                                        </p>
                                        <VehicleLoanStatusBadge
                                            status={loan.status}
                                        />
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
