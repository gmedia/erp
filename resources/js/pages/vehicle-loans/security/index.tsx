import { PageHeader } from '@/components/common/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Helmet } from 'react-helmet-async';
import { toast } from 'sonner';

export default function VehicleLoanSecurityPage() {
    const queryClient = useQueryClient();

    const { data } = useQuery({
        queryKey: ['vehicle-loans', 'security-queue'],
        queryFn: async () => {
            const response = await axios.get(
                '/api/vehicle-loans/security-queue',
            );
            return response.data.data as Array<{
                id: number;
                code: string;
                status: string;
                requester_name: string;
                plate_number: string | null;
                driver_name: string | null;
                start_at: string;
                pickup?: { id: number; key_handover_at: string | null } | null;
            }>;
        },
    });

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
                { title: 'Security Queue', href: '/vehicle-loans/security' },
            ]}
        >
            <Helmet>
                <title>Daftar Peminjaman — Security </title>
            </Helmet>
            <div className="mx-auto max-w-5xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title="Daftar Peminjaman Kendaraan"
                    description="Verifikasi status approval sebelum menyerahkan kunci kendaraan."
                />

                <div className="grid gap-3 md:grid-cols-2">
                    {data?.map((loan) => {
                        const canHandover =
                            loan.pickup && !loan.pickup.key_handover_at;
                        return (
                            <Card key={loan.id}>
                                <CardContent className="space-y-2 pt-4">
                                    <div className="flex items-center justify-between">
                                        <strong>{loan.code}</strong>
                                        <Badge
                                            variant={
                                                loan.status === 'approved'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {loan.status}
                                        </Badge>
                                    </div>
                                    <p className="text-sm">
                                        Peminjam: {loan.requester_name}
                                    </p>
                                    <p className="text-sm">
                                        Unit: {loan.plate_number ?? '-'}
                                    </p>
                                    <p className="text-sm">
                                        Driver: {loan.driver_name ?? '-'}
                                    </p>
                                    <p className="text-sm">
                                        Mulai:{' '}
                                        {new Date(
                                            loan.start_at,
                                        ).toLocaleString()}
                                    </p>
                                    {loan.pickup?.key_handover_at ? (
                                        <p className="text-xs text-green-600">
                                            Kunci sudah diserahkan
                                        </p>
                                    ) : canHandover ? (
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                handover(loan.pickup!.id)
                                            }
                                        >
                                            Serahkan Kunci
                                        </Button>
                                    ) : (
                                        <p className="text-xs text-muted-foreground">
                                            Menunggu form pengambilan
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
