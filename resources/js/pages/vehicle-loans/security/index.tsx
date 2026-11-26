import { PageHeader } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { VehicleLoanStatusBadge } from '@/components/vehicle-loans/VehicleLoanStatusBadge';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import { useToastTranslator } from '@/utils/i18nFallback';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo, useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';

interface SecurityLoan {
    id: number;
    code: string;
    status: string;
    requester_name: string;
    plate_number: string | null;
    driver_name: string | null;
    start_at: string;
    end_at: string;
    is_overdue?: boolean;
    approval_requests?: Array<{ status: string }>;
    pickup?: { id: number; key_handover_at: string | null } | null;
}

type QueueFilter = 'all' | 'ready' | 'handed';

export default function VehicleLoanSecurityPage() {
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [filter, setFilter] = useState<QueueFilter>('all');
    const tToast = useToastTranslator('vehicle_loan_toast');

    const { data, isLoading } = useQuery({
        queryKey: ['vehicle-loans', 'security-queue'],
        queryFn: async () => {
            const response = await axios.get(
                '/api/vehicle-loans/security-queue',
            );
            return response.data.data as SecurityLoan[];
        },
    });

    const loans = useMemo(() => data ?? [], [data]);

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        return loans.filter((loan) => {
            const handed = Boolean(loan.pickup?.key_handover_at);
            const ready = Boolean(loan.pickup) && !handed;

            if (filter === 'ready' && !ready) return false;
            if (filter === 'handed' && !handed) return false;

            if (!term) return true;

            return [
                loan.code,
                loan.requester_name,
                loan.plate_number,
                loan.driver_name,
            ]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(term));
        });
    }, [loans, filter, search]);

    async function handover(pickupId: number) {
        try {
            await axios.post(`/api/vehicle-loans/pickups/${pickupId}/handover`);
            toast.success(
                tToast('security_key_handed_over', 'Kunci diserahkan.'),
            );
            void queryClient.invalidateQueries({
                queryKey: ['vehicle-loans', 'security-queue'],
            });
            void queryClient.invalidateQueries({
                queryKey: ['vehicle-loans', 'dashboard'],
            });
        } catch {
            toast.error(
                tToast(
                    'security_handover_failed',
                    'Gagal menyerahkan kunci. Pastikan persetujuan sudah final.',
                ),
            );
        }
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Antrean Keamanan', href: '/vehicle-loans/security' },
            ]}
        >
            <Helmet>
                <title>Antrean Keamanan — Peminjaman Kendaraan</title>
            </Helmet>
            <div className="mx-auto max-w-7xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title="Antrean Keamanan"
                    description="Verifikasi status persetujuan sebelum menyerahkan kunci kendaraan."
                />

                <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_220px_auto] sm:items-end">
                    <div className="grid gap-1">
                        <Label htmlFor="security-search" className="text-xs">
                            Cari
                        </Label>
                        <Input
                            id="security-search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari kode atau plat"
                        />
                    </div>
                    <div className="grid gap-1">
                        <Label htmlFor="security-filter" className="text-xs">
                            Status antrean
                        </Label>
                        <Select
                            value={filter}
                            onValueChange={(value) =>
                                setFilter(value as QueueFilter)
                            }
                        >
                            <SelectTrigger id="security-filter">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua permintaan
                                </SelectItem>
                                <SelectItem value="ready">
                                    Siap diserahkan
                                </SelectItem>
                                <SelectItem value="handed">
                                    Sudah diserahkan
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <p
                        className="text-sm text-muted-foreground"
                        aria-live="polite"
                    >
                        {filtered.length} permintaan
                    </p>
                </div>

                {isLoading && (
                    <Card>
                        <CardContent className="py-10 text-center text-sm text-muted-foreground">
                            Memuat antrean keamanan...
                        </CardContent>
                    </Card>
                )}
                {!isLoading && filtered.length === 0 && (
                    <Card>
                        <CardContent className="space-y-3 py-10 text-center">
                            <div>
                                <p className="font-medium">
                                    Tidak ada permintaan pada filter ini
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Ubah filter atau kata pencarian untuk
                                    melihat antrean lain.
                                </p>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    setFilter('all');
                                    setSearch('');
                                }}
                            >
                                Tampilkan Semua
                            </Button>
                        </CardContent>
                    </Card>
                )}
                {!isLoading && filtered.length > 0 && (
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {filtered.map((loan) => {
                            const handed = Boolean(
                                loan.pickup?.key_handover_at,
                            );
                            const ready = Boolean(loan.pickup) && !handed;
                            const approvalStatus =
                                loan.approval_requests?.[0]?.status;

                            return (
                                <Card key={loan.id} className="flex flex-col">
                                    <CardContent className="flex flex-1 flex-col gap-3 pt-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <div>
                                                <p className="font-semibold">
                                                    {loan.code}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Peminjam:{' '}
                                                    {loan.requester_name}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap items-center justify-end gap-1">
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
                                        <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
                                            <dt className="text-muted-foreground">
                                                Kendaraan
                                            </dt>
                                            <dd>{loan.plate_number ?? '-'}</dd>
                                            <dt className="text-muted-foreground">
                                                Pengemudi
                                            </dt>
                                            <dd>{loan.driver_name ?? '-'}</dd>
                                            <dt className="text-muted-foreground">
                                                Mulai
                                            </dt>
                                            <dd>
                                                {formatDateTimeByRegionalSettings(
                                                    loan.start_at,
                                                )}
                                            </dd>
                                            <dt className="text-muted-foreground">
                                                Selesai
                                            </dt>
                                            <dd>
                                                {formatDateTimeByRegionalSettings(
                                                    loan.end_at,
                                                )}
                                            </dd>
                                            <dt className="text-muted-foreground">
                                                Persetujuan
                                            </dt>
                                            <dd>
                                                {approvalStatus ? (
                                                    <VehicleLoanStatusBadge
                                                        status={approvalStatus}
                                                    />
                                                ) : (
                                                    '-'
                                                )}
                                            </dd>
                                        </dl>
                                        <div className="mt-auto flex items-center justify-between gap-2 border-t pt-3">
                                            <div className="min-w-0">
                                                {handed && (
                                                    <span className="inline-flex items-center rounded-full bg-success-soft px-2 py-1 text-xs font-medium text-success">
                                                        Kunci sudah diserahkan
                                                    </span>
                                                )}
                                                {ready && (
                                                    <Button
                                                        size="sm"
                                                        className="min-h-10"
                                                        onClick={() =>
                                                            void handover(
                                                                loan.pickup!.id,
                                                            )
                                                        }
                                                    >
                                                        Serahkan Kunci
                                                    </Button>
                                                )}
                                                {!handed && !ready && (
                                                    <span className="text-xs text-muted-foreground">
                                                        Menunggu pengambilan
                                                    </span>
                                                )}
                                            </div>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="min-h-10"
                                                asChild
                                            >
                                                <Link
                                                    to={`/vehicle-loans/${loan.id}`}
                                                >
                                                    Detail
                                                </Link>
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
