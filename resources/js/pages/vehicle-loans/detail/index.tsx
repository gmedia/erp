import { PageHeader } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { VehicleLoanStatusBadge } from '@/components/vehicle-loans/VehicleLoanStatusBadge';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import { useQuery } from '@tanstack/react-query';
import { Helmet } from 'react-helmet-async';
import { Link, useParams } from 'react-router-dom';

interface ApprovalStep {
    step_order: number;
    status: string;
    action: string | null;
    comments: string | null;
    acted_at: string | null;
    approver_name: string | null;
}

interface ApprovalRequest {
    id: number;
    status: string;
    current_step_order: number | null;
    submitted_at: string | null;
    completed_at: string | null;
    steps: ApprovalStep[];
}

interface ActivityLog {
    id: number;
    event: string;
    actor_name: string | null;
    created_at: string | null;
}

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
    submitted_at: string | null;
    approved_at: string | null;
    created_at: string | null;
    approval_requests?: ApprovalRequest[];
    activity_logs?: ActivityLog[];
    pickup?: {
        key_handover_at: string | null;
        picked_at: string | null;
    } | null;
    returns?: { returned_at: string | null; incident: boolean } | null;
}

function activityLabel(event: string): string {
    return event
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function Field({
    label,
    value,
}: Readonly<{ label: string; value: string | null | undefined }>) {
    return (
        <div className="grid gap-0.5">
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="text-sm font-medium">{value || '-'}</dd>
        </div>
    );
}

export default function VehicleLoanDetailPage() {
    const { id } = useParams();

    const { data, isLoading } = useQuery<VehicleLoanDetail>({
        queryKey: ['vehicle-loans', 'detail', id],
        queryFn: async () => {
            const response = await axios.get(`/api/vehicle-loans/${id}`);
            return response.data.data;
        },
        enabled: Boolean(id),
    });

    const approval = data?.approval_requests?.[0];
    const steps = approval?.steps ?? [];

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Detail Peminjaman', href: `/vehicle-loans/${id}` },
            ]}
        >
            <Helmet>
                <title>Detail Peminjaman Kendaraan</title>
            </Helmet>
            <div className="mx-auto max-w-6xl space-y-4 p-4 md:p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <PageHeader
                        title={`Permintaan ${data?.code ?? ''}`}
                        description="Detail permintaan, approval, dan riwayat peminjaman."
                    />
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link to="/vehicle-loans/dashboard">Kembali</Link>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.print()}
                        >
                            Cetak
                        </Button>
                    </div>
                </div>

                {isLoading && (
                    <Card>
                        <CardContent className="py-10 text-center text-sm text-muted-foreground">
                            Memuat detail permintaan...
                        </CardContent>
                    </Card>
                )}

                {!isLoading && !data && (
                    <Card>
                        <CardContent className="space-y-1 py-10 text-center">
                            <p className="font-medium">
                                Permintaan tidak ditemukan
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Data mungkin sudah dihapus atau Anda tidak
                                memiliki akses.
                            </p>
                        </CardContent>
                    </Card>
                )}

                {data && (
                    <div className="grid gap-4 lg:grid-cols-3">
                        <div className="space-y-4 lg:col-span-2">
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base">
                                        Informasi Peminjam
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <dl className="grid gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Peminjam"
                                            value={data.requester_name}
                                        />
                                        <Field
                                            label="Department"
                                            value={data.department_name}
                                        />
                                        <Field
                                            label="Mulai"
                                            value={formatDateTimeByRegionalSettings(
                                                data.start_at,
                                            )}
                                        />
                                        <Field
                                            label="Selesai"
                                            value={formatDateTimeByRegionalSettings(
                                                data.end_at,
                                            )}
                                        />
                                        <Field
                                            label="Tujuan"
                                            value={data.purpose}
                                        />
                                        <Field
                                            label="Diajukan"
                                            value={formatDateTimeByRegionalSettings(
                                                data.submitted_at,
                                            )}
                                        />
                                    </dl>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base">
                                        Kendaraan & Driver
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <dl className="grid gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Driver"
                                            value={data.driver_name}
                                        />
                                        <Field
                                            label="Unit kendaraan"
                                            value={data.plate_number}
                                        />
                                        <Field
                                            label="Disetujui"
                                            value={formatDateTimeByRegionalSettings(
                                                data.approved_at,
                                            )}
                                        />
                                        <Field
                                            label="Kunci diserahkan"
                                            value={formatDateTimeByRegionalSettings(
                                                data.pickup?.key_handover_at,
                                            )}
                                        />
                                        <Field
                                            label="Dikembalikan"
                                            value={formatDateTimeByRegionalSettings(
                                                data.returns?.returned_at,
                                            )}
                                        />
                                        <Field
                                            label="Insiden"
                                            value={
                                                data.returns
                                                    ? data.returns.incident
                                                        ? 'Ada insiden'
                                                        : 'Tidak ada'
                                                    : null
                                            }
                                        />
                                    </dl>
                                    {data.review_notes && (
                                        <p className="mt-4 rounded-md bg-muted/40 p-3 text-sm">
                                            Catatan review: {data.review_notes}
                                        </p>
                                    )}
                                    {data.rejection_reason && (
                                        <p className="mt-4 rounded-md bg-rose-50 p-3 text-sm text-rose-800">
                                            Alasan ditolak:{' '}
                                            {data.rejection_reason}
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base">
                                        Riwayat Aktivitas
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {!data.activity_logs?.length ? (
                                        <p className="text-sm text-muted-foreground">
                                            Belum ada aktivitas tercatat.
                                        </p>
                                    ) : (
                                        <ol className="space-y-3">
                                            {data.activity_logs.map((log) => (
                                                <li
                                                    key={log.id}
                                                    className="flex gap-3"
                                                >
                                                    <span className="mt-1.5 size-2 shrink-0 rounded-full bg-slate-400" />
                                                    <div>
                                                        <p className="text-sm font-medium">
                                                            {activityLabel(
                                                                log.event,
                                                            )}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {formatDateTimeByRegionalSettings(
                                                                log.created_at,
                                                            )}
                                                            {log.actor_name
                                                                ? ` · ${log.actor_name}`
                                                                : ''}
                                                        </p>
                                                    </div>
                                                </li>
                                            ))}
                                        </ol>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        <div className="space-y-4">
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base">
                                        Status
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <VehicleLoanStatusBadge
                                        status={data.status}
                                    />
                                    <dl className="space-y-2 text-sm">
                                        <div className="flex justify-between gap-3">
                                            <dt className="text-muted-foreground">
                                                Diajukan
                                            </dt>
                                            <dd>
                                                {formatDateTimeByRegionalSettings(
                                                    data.submitted_at,
                                                )}
                                            </dd>
                                        </div>
                                        <div className="flex justify-between gap-3">
                                            <dt className="text-muted-foreground">
                                                Disetujui
                                            </dt>
                                            <dd>
                                                {formatDateTimeByRegionalSettings(
                                                    data.approved_at,
                                                )}
                                            </dd>
                                        </div>
                                    </dl>
                                    {(data.status === 'pending' ||
                                        data.status === 'in_progress') && (
                                        <Button className="w-full" asChild>
                                            <Link to="/my-approvals">
                                                Buka My Approvals
                                            </Link>
                                        </Button>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base">
                                        Approval
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {!approval || steps.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            Belum ada langkah approval.
                                        </p>
                                    ) : (
                                        <ol className="space-y-4">
                                            {steps.map((step) => (
                                                <li
                                                    key={step.step_order}
                                                    className="flex gap-3"
                                                >
                                                    <span
                                                        className={`mt-1 flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold ${
                                                            step.status ===
                                                            'approved'
                                                                ? 'bg-emerald-100 text-emerald-700'
                                                                : step.status ===
                                                                    'rejected'
                                                                  ? 'bg-rose-100 text-rose-700'
                                                                  : 'bg-amber-100 text-amber-700'
                                                        }`}
                                                    >
                                                        {step.step_order}
                                                    </span>
                                                    <div className="space-y-1">
                                                        <p className="text-sm font-medium">
                                                            Langkah{' '}
                                                            {step.step_order} ·{' '}
                                                            {activityLabel(
                                                                step.status,
                                                            )}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {step.approver_name ??
                                                                'Approver'}
                                                            {step.acted_at
                                                                ? ` · ${formatDateTimeByRegionalSettings(step.acted_at)}`
                                                                : ''}
                                                        </p>
                                                        {step.comments && (
                                                            <p className="text-xs text-muted-foreground">
                                                                “{step.comments}
                                                                ”
                                                            </p>
                                                        )}
                                                    </div>
                                                </li>
                                            ))}
                                        </ol>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
