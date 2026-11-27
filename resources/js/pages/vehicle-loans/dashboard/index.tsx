import { PageHeader } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { VehicleLoanStatusBadge } from '@/components/vehicle-loans/VehicleLoanStatusBadge';
import { useVehicleLoanPageText } from '@/components/vehicle-loans/useVehicleLoanPageText';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import {
    AWAITING_KEY_HANDOVER,
    vehicleLoanDisplayStatus,
} from '@/utils/vehicle-loan-status';
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
    pickup?: { key_handover_at: string | null } | null;
}

export default function VehicleLoanDashboardPage() {
    const tPage = useVehicleLoanPageText();
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
    const awaitingKeyHandover = loans.filter(
        (loan) => vehicleLoanDisplayStatus(loan) === AWAITING_KEY_HANDOVER,
    ).length;
    const handedOver = count('pickup_done') - awaitingKeyHandover;
    const overdue = loans.filter((loan) => loan.is_overdue).length;
    const active = loans.filter(
        (loan) =>
            vehicleLoanDisplayStatus(loan) === 'pickup_done' &&
            !loan.is_overdue &&
            loan.end_at !== null &&
            new Date(loan.end_at) >= new Date(),
    ).length;

    const statusCards = [
        {
            label: tPage('dashboard.status_total', 'Total permintaan'),
            value: loans.length,
            icon: FileText,
            tone: 'text-muted-foreground bg-muted',
        },
        {
            label: tPage('dashboard.status_draft', 'Draf'),
            value: count('draft'),
            icon: FileText,
            tone: 'text-muted-foreground bg-muted',
        },
        {
            label: tPage('dashboard.status_pending', 'Menunggu Persetujuan'),
            value: count('pending') + count('in_progress'),
            icon: Clock3,
            tone: 'text-warning bg-warning-soft',
        },
        {
            label: tPage('dashboard.status_approved', 'Disetujui'),
            value: count('approved'),
            icon: CheckCircle2,
            tone: 'text-success bg-success-soft',
        },
        {
            label: tPage('dashboard.status_rejected', 'Ditolak'),
            value: count('rejected'),
            icon: XCircle,
            tone: 'text-destructive-foreground bg-destructive-soft',
        },
        {
            label: tPage(
                'dashboard.status_awaiting_key',
                'Menunggu Serah Kunci',
            ),
            value: awaitingKeyHandover,
            icon: KeyRound,
            tone: 'text-warning bg-warning-soft',
        },
        {
            label: tPage('dashboard.status_in_use', 'Kendaraan Dipakai'),
            value: handedOver,
            icon: KeyRound,
            tone: 'text-info bg-info/10',
        },
        {
            label: tPage('dashboard.status_completed', 'Selesai'),
            value: count('returned'),
            icon: CarFront,
            tone: 'text-success bg-success-soft',
        },
        {
            label: tPage('dashboard.status_cancelled', 'Dibatalkan'),
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
                    title: tPage('request.breadcrumb', 'Peminjaman Kendaraan'),
                    href: '/vehicle-loans/request',
                },
                {
                    title: tPage('dashboard.breadcrumb', 'Dasbor Peminjaman'),
                    href: '/vehicle-loans/dashboard',
                },
            ]}
        >
            <Helmet>
                <title>
                    {tPage('dashboard.title', 'Dasbor Peminjaman Kendaraan')}
                </title>
            </Helmet>
            <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
                <PageHeader
                    title={tPage(
                        'dashboard.title',
                        'Dasbor Peminjaman Kendaraan',
                    )}
                    description={tPage(
                        'dashboard.description',
                        'Ringkasan status permintaan, keterlambatan, dan aktivitas terbaru.',
                    )}
                />

                <section className="space-y-3">
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <h2 className="text-sm font-semibold">
                            {tPage(
                                'dashboard.heading_status',
                                'Status Permintaan',
                            )}
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            {tPage(
                                'dashboard.updated_at',
                                'Diperbarui {time}',
                                {
                                    time: formatDateTimeByRegionalSettings(
                                        new Date(dataUpdatedAt).toISOString(),
                                    ),
                                },
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
                            <CardTitle className="flex items-center gap-2 text-base">
                                <AlertTriangle className="size-4 text-warning" />
                                {tPage(
                                    'dashboard.overdue_card_title',
                                    'Terlambat dikembalikan',
                                )}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{overdue}</p>
                            <p className="text-xs text-muted-foreground">
                                {tPage(
                                    'dashboard.overdue_card_description',
                                    'Kendaraan melewati jadwal pengembalian.',
                                )}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <CarFront className="size-4 text-info" />
                                {tPage(
                                    'dashboard.active_card_title',
                                    'Aktif (belum terlambat)',
                                )}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{active}</p>
                            <p className="text-xs text-muted-foreground">
                                {tPage(
                                    'dashboard.active_card_description',
                                    'Kunci diserahkan, belum dikembalikan, dan belum melewati tenggat.',
                                )}
                            </p>
                        </CardContent>
                    </Card>
                </section>

                <section className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-semibold">
                            {tPage(
                                'dashboard.heading_recent',
                                'Permintaan Terbaru',
                            )}
                        </h2>
                        <Button variant="outline" size="sm" asChild>
                            <Link to="/vehicle-loans/security">
                                {tPage('dashboard.view_queue', 'Lihat Antrean')}
                            </Link>
                        </Button>
                    </div>
                    <Card>
                        <CardContent className="divide-y py-0">
                            {recentLoans.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    {tPage(
                                        'dashboard.empty',
                                        'Belum ada permintaan peminjaman.',
                                    )}
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
                                                    tPage(
                                                        'dashboard.unit_undetermined',
                                                        'unit belum ditentukan',
                                                    )}
                                            </p>
                                        </div>
                                        <p className="text-xs text-muted-foreground tabular-nums sm:w-36 sm:shrink-0 sm:whitespace-nowrap">
                                            {formatDateTimeByRegionalSettings(
                                                loan.start_at,
                                            )}
                                        </p>
                                        <div className="flex items-center gap-2 self-start sm:self-auto">
                                            {loan.is_overdue && (
                                                <span className="rounded-full bg-destructive-soft px-2 py-1 text-xs font-medium text-destructive-foreground">
                                                    {tPage(
                                                        'dashboard.overdue_badge',
                                                        'Terlambat',
                                                    )}
                                                </span>
                                            )}
                                            <VehicleLoanStatusBadge
                                                loan={loan}
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
