import { PageHeader } from '@/components/common/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';
import { Helmet } from 'react-helmet-async';
import { useParams } from 'react-router-dom';

interface VehicleLoanDetail {
    id: number;
    code: string;
    status: string;
    requester_name: string | null;
    department_name: string | null;
    purpose: string | null;
    start_at: string | null;
    end_at: string | null;
    driver_name: string | null;
    plate_number: string | null;
    rejection_reason: string | null;
    review_notes: string | null;
}

function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '-' : date.toLocaleString();
}

export default function VehicleLoanDetailPage() {
    const { id } = useParams();

    const { data } = useQuery<VehicleLoanDetail>({
        queryKey: ['vehicle-loans', 'detail', id],
        queryFn: async () => {
            const response = await axios.get(`/api/vehicle-loans/${id}`);
            return response.data.data;
        },
        enabled: Boolean(id),
    });

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Detail Peminjaman', href: `/vehicle-loans/${id}` },
            ]}
        >
            <Helmet>
                <title>Detail Peminjaman Kendaraan </title>
            </Helmet>
            <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title={`Permintaan ${data?.code ?? ''}`}
                    description="Detail permintaan, approval, dan riwayat peminjaman."
                />

                {data && (
                    <Card>
                        <CardContent className="space-y-2 pt-4 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Status
                                </span>
                                <Badge>{data.status}</Badge>
                            </div>
                            <p>Peminjam: {data.requester_name ?? '-'}</p>
                            <p>Department: {data.department_name ?? '-'}</p>
                            <p>Tujuan: {data.purpose ?? '-'}</p>
                            <p>Mulai: {formatDateTime(data.start_at)}</p>
                            <p>Selesai: {formatDateTime(data.end_at)}</p>
                            <p>Driver: {data.driver_name ?? '-'}</p>
                            <p>Unit: {data.plate_number ?? '-'}</p>
                            {data.rejection_reason && (
                                <p className="text-red-600">
                                    Alasan ditolak: {data.rejection_reason}
                                </p>
                            )}
                            {data.review_notes && (
                                <p>Catatan review: {data.review_notes}</p>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
