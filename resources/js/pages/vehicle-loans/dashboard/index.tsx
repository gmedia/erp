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
    end_at: string | null;
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
    const active = loans.filter(
        (loan) =>
            loan.status === 'pickup_done' &&
            !loan.is_overdue &&
            loan.end_at !== null &&
            new Date(loan.end_at) >= new Date(),
    ).length;

    const statusCards = [
        {
            label: 'Total permintaan',
            value: loans.length,
            icon: FileText,
            tone: 'text-muted-foreground bg-muted',
        },
        {
            label: 'Draf',
            value: count('draft'),
            icon: FileText,
            tone: 'text-muted-foreground bg-muted',
        },
        {
            label: 'Menunggu Persetujuan',
            value: count('pending') + count('in_progress'),
            icon: Clock3,
            tone: 'text-warning bg-warning-soft',
        },
        {
            label: 'Disetujui',
            value: count('approved'),
            icon: CheckCircle2,
            tone: 'text-success bg-success-soft',
        },
        {
            label: 'Ditolak',
            value: count('rejected'),
            icon: XCircle,
            tone: 'text-destructive-foreground bg-destructive-soft',
        },
        {
            label: 'Kendaraan Dipakai',
            value: count('pickup_done'),
            icon: KeyRound,
            tone: 'text-info bg-info/10',
        },
        {
            label: 'Selesai',
            value: count('returned'),
            icon: CarFront,
            tone: 'text-success bg-success-soft',
        },
        {
            label: 'Dibatalkan',
            value: count('cancelled'),
            icon: Ban,
            tone: 'text-muted-foreground bg-muted',
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
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <h2 className="text-sm font-semibold">
                            Status Permintaan
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            Diperbarui{' '}
                            {formatDateTimeByRegionalSettings(
                                new Date(dataUpdatedAt).toISOString(),
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
                                            <p className="text-xs leading-tight text-muted-foreground">
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
                                <AlertTriangle className="size-4 text-warning" />
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
                                <CarFront className="size-4 text-info" />
                                Aktif (belum terlambat)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{active}</p>
                            <p className="text-xs text-muted-foreground">
                                Kunci diserahkan, belum dikembalikan, dan belum
                                melewati tenggat.
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
                                Lihat Antrean
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
                                        className="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:gap-3"
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
                                        <p className="text-xs text-muted-foreground sm:whitespace-nowrap">
                                            {formatDateTimeByRegionalSettings(
                                                loan.start_at,
                                            )}
                                        </p>
                                        <div className="flex items-center gap-2 self-start sm:self-auto">
                                            {loan.is_overdue && (
                                                <span className="rounded-full bg-destructive-soft px-2 py-1 text-xs font-medium text-destructive-foreground">
                                                    Terlambat
                                                </span>
                                            )}
                                            <VehicleLoanStatusBadge
                                                status={loan.status}
                                            />
                                        </div>
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
