import { PageHeader } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
    pickup?: { id: number; key_handover_at: string | null } | null;
}

type QueueFilter = 'all' | 'ready' | 'handed';

export default function VehicleLoanSecurityPage() {
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [filter, setFilter] = useState<QueueFilter>('ready');

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
            toast.success('Kunci diserahkan.');
            void queryClient.invalidateQueries({
                queryKey: ['vehicle-loans', 'security-queue'],
            });
        } catch {
            toast.error(
                'Gagal menyerahkan kunci. Pastikan approval sudah final.',
            );
        }
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Antrean Security', href: '/vehicle-loans/security' },
            ]}
        >
            <Helmet>
                <title>Daftar Peminjaman — Security</title>
            </Helmet>
            <div className="mx-auto max-w-7xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title="Daftar Peminjaman Kendaraan"
                    description="Verifikasi status approval sebelum menyerahkan kunci kendaraan."
                />

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Cari kode, peminjam, plat, atau driver"
                        className="sm:max-w-sm"
                    />
                    <Select
                        value={filter}
                        onValueChange={(value) =>
                            setFilter(value as QueueFilter)
                        }
                    >
                        <SelectTrigger className="sm:w-52">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ready">
                                Siap diserahkan
                            </SelectItem>
                            <SelectItem value="handed">
                                Sudah diserahkan
                            </SelectItem>
                            <SelectItem value="all">
                                Semua permintaan
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p className="text-sm text-muted-foreground sm:ml-auto">
                        {filtered.length} permintaan
                    </p>
                </div>

                {isLoading ? (
                    <Card>
                        <CardContent className="py-10 text-center text-sm text-muted-foreground">
                            Memuat antrean security...
                        </CardContent>
                    </Card>
                ) : filtered.length === 0 ? (
                    <Card>
                        <CardContent className="space-y-1 py-10 text-center">
                            <p className="font-medium">
                                Tidak ada permintaan pada filter ini
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Coba ubah filter atau kata pencarian.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {filtered.map((loan) => {
                            const handed = Boolean(
                                loan.pickup?.key_handover_at,
                            );
                            const ready = Boolean(loan.pickup) && !handed;

                            return (
                                <Card key={loan.id} className="flex flex-col">
                                    <CardContent className="flex flex-1 flex-col gap-3 pt-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <div>
                                                <p className="font-semibold">
                                                    {loan.code}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {loan.requester_name}
                                                </p>
                                            </div>
                                            <VehicleLoanStatusBadge
                                                status={loan.status}
                                            />
                                        </div>
                                        <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
                                            <dt className="text-muted-foreground">
                                                Unit
                                            </dt>
                                            <dd>{loan.plate_number ?? '-'}</dd>
                                            <dt className="text-muted-foreground">
                                                Driver
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
                                        </dl>
                                        <div className="mt-auto flex flex-wrap items-center gap-2 border-t pt-3">
                                            {handed ? (
                                                <span className="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">
                                                    Kunci sudah diserahkan
                                                </span>
                                            ) : ready ? (
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        void handover(
                                                            loan.pickup!.id,
                                                        )
                                                    }
                                                >
                                                    Serahkan Kunci
                                                </Button>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">
                                                    Menunggu form pengambilan
                                                    dari peminjam
                                                </span>
                                            )}
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <Link
                                                    to={`/vehicle-loans/${loan.id}`}
                                                >
                                                    Lihat Detail
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
