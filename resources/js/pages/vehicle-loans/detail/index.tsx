import { PageHeader } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useVehicleLoanPageText } from '@/components/vehicle-loans/useVehicleLoanPageText';
import { VehicleLoanStatusBadge } from '@/components/vehicle-loans/VehicleLoanStatusBadge';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import { approvalStepStatusLabel } from '@/utils/vehicle-loan-status';
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
    is_overdue?: boolean;
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

type PageText = (
    key: string,
    fallback: string,
    params?: Record<string, string>,
) => string;

function activityLabel(event: string, tPage: PageText): string {
    switch (event) {
        case 'draft_created':
            return tPage('activity.draft_created', 'Draf dibuat');
        case 'submitted':
            return tPage('activity.submitted', 'Permintaan dikirim');
        case 'approval_step_advanced':
            return tPage(
                'activity.approval_step_advanced',
                'Persetujuan lanjutan',
            );
        case 'approval_completed':
            return tPage('activity.approval_completed', 'Persetujuan selesai');
        case 'approval_rejected':
            return tPage('activity.approval_rejected', 'Permintaan ditolak');
        case 'assignment_changed':
            return tPage('activity.assignment_changed', 'Penugasan diubah');
        case 'cancelled':
            return tPage('activity.cancelled', 'Permintaan dibatalkan');
        case 'pickup_submitted':
            return tPage('activity.pickup_submitted', 'Pengambilan dikirim');
        case 'key_handed_over':
            return tPage('activity.key_handed_over', 'Kunci diserahkan');
        case 'return_submitted':
            return tPage('activity.return_submitted', 'Pengembalian dikirim');
        case 'return_overdue':
            return tPage('activity.return_overdue', 'Pengembalian terlambat');
        default:
            return event.replaceAll('_', ' ');
    }
}

const STEP_STATUS_CLASS: Record<string, string> = {
    approved: 'bg-success-soft text-success',
    rejected: 'bg-destructive-soft text-destructive-foreground',
};

function incidentLabel(
    returns: { incident?: boolean | null } | null | undefined,
    texts: { yes: string; no: string },
): string | null {
    if (!returns) {
        return null;
    }
    return returns.incident ? texts.yes : texts.no;
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
    const tPage = useVehicleLoanPageText();

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

    const action = (() => {
        if (!data) return null;
        if (data.status === 'draft') {
            return {
                label: tPage(
                    'detail_page.action_request',
                    'Lanjutkan Permintaan',
                ),
                to: '/vehicle-loans/request',
            };
        }
        if (data.status === 'pending' || data.status === 'in_progress') {
            return {
                label: tPage(
                    'detail_page.action_approvals',
                    'Buka My Approvals',
                ),
                to: '/my-approvals',
            };
        }
        if (data.status === 'approved') {
            return {
                label: tPage('detail_page.action_pickup', 'Buat Pengambilan'),
                to: '/vehicle-loans/pickup',
            };
        }
        if (data.status === 'pickup_done') {
            if (!data.pickup?.key_handover_at) {
                return {
                    label: tPage(
                        'detail_page.action_security',
                        'Buka Antrean Keamanan',
                    ),
                    to: '/vehicle-loans/security',
                };
            }
            return {
                label: tPage('detail_page.action_return', 'Buat Pengembalian'),
                to: '/vehicle-loans/return',
            };
        }
        return null;
    })();

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: tPage('detail_page.breadcrumb', 'Detail Peminjaman'),
                    href: `/vehicle-loans/${id}`,
                },
            ]}
        >
            <Helmet>
                <title>
                    {tPage(
                        'detail_page.page_title',
                        'Detail Peminjaman Kendaraan',
                    )}
                </title>
            </Helmet>
            <div className="mx-auto max-w-6xl space-y-4 p-4 md:p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <PageHeader
                        title={tPage(
                            'detail_page.title_prefix',
                            'Permintaan {code}',
                            {
                                code: data?.code ?? '',
                            },
                        )}
                        description={tPage(
                            'detail_page.description',
                            'Detail permintaan, persetujuan, dan riwayat peminjaman.',
                        )}
                    />
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link to="/vehicle-loans/dashboard">
                                {tPage('detail_page.back', 'Kembali')}
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.print()}
                        >
                            {tPage('detail_page.print', 'Cetak')}
                        </Button>
                    </div>
                </div>

                {isLoading && (
                    <Card>
                        <CardContent className="py-10 text-center text-sm text-muted-foreground">
                            {tPage(
                                'detail_page.loading',
                                'Memuat detail permintaan...',
                            )}
                        </CardContent>
                    </Card>
                )}

                {!isLoading && !data && (
                    <Card>
                        <CardContent className="space-y-1 py-10 text-center">
                            <p className="font-medium">
                                {tPage(
                                    'detail_page.not_found_title',
                                    'Permintaan tidak ditemukan',
                                )}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {tPage(
                                    'detail_page.not_found_description',
                                    'Data mungkin sudah dihapus atau Anda tidak memiliki akses.',
                                )}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {data && (
                    <>
                        <div className="grid gap-4 lg:grid-cols-3">
                            <div className="space-y-4 lg:col-span-2">
                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-base">
                                            {tPage(
                                                'detail_page.section_borrower',
                                                'Informasi Peminjam',
                                            )}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <dl className="grid gap-4 sm:grid-cols-2">
                                            <Field
                                                label={tPage(
                                                    'detail_page.requester_label',
                                                    'Peminjam',
                                                )}
                                                value={data.requester_name}
                                            />
                                            <Field
                                                label={tPage(
                                                    'detail_page.department_label',
                                                    'Departemen',
                                                )}
                                                value={data.department_name}
                                            />
                                            <Field
                                                label={tPage(
                                                    'detail_page.start_label',
                                                    'Mulai',
                                                )}
                                                value={formatDateTimeByRegionalSettings(
                                                    data.start_at,
                                                )}
                                            />
                                            <Field
                                                label={tPage(
                                                    'detail_page.end_label',
                                                    'Batas Pengembalian',
                                                )}
                                                value={formatDateTimeByRegionalSettings(
                                                    data.end_at,
                                                )}
                                            />
                                            <Field
                                                label={tPage(
                                                    'detail_page.purpose_label',
                                                    'Tujuan',
                                                )}
                                                value={data.purpose}
                                            />
                                            <Field
                                                label={tPage(
                                                    'detail_page.submitted_label',
                                                    'Diajukan',
                                                )}
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
                                            {tPage(
                                                'detail_page.section_vehicle',
                                                'Kendaraan & Pengemudi',
                                            )}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <dl className="grid gap-4 sm:grid-cols-2">
                                            <Field
                                                label={tPage(
                                                    'detail_page.driver_label',
                                                    'Pengemudi',
                                                )}
                                                value={data.driver_name}
                                            />
                                            <Field
                                                label={tPage(
                                                    'detail_page.vehicle_label',
                                                    'Kendaraan',
                                                )}
                                                value={data.plate_number}
                                            />
                                            <Field
                                                label={tPage(
                                                    'detail_page.key_handover_label',
                                                    'Kunci diserahkan',
                                                )}
                                                value={formatDateTimeByRegionalSettings(
                                                    data.pickup
                                                        ?.key_handover_at,
                                                )}
                                            />
                                            <Field
                                                label={tPage(
                                                    'detail_page.returned_label',
                                                    'Dikembalikan',
                                                )}
                                                value={formatDateTimeByRegionalSettings(
                                                    data.returns?.returned_at,
                                                )}
                                            />
                                            <Field
                                                label={tPage(
                                                    'detail_page.incident_label',
                                                    'Insiden',
                                                )}
                                                value={incidentLabel(
                                                    data.returns,
                                                    {
                                                        yes: tPage(
                                                            'detail_page.incident_yes',
                                                            'Ada insiden',
                                                        ),
                                                        no: tPage(
                                                            'detail_page.incident_no',
                                                            'Tidak ada',
                                                        ),
                                                    },
                                                )}
                                            />
                                        </dl>
                                        {data.review_notes && (
                                            <p className="mt-4 rounded-md bg-muted/40 p-3 text-sm">
                                                {tPage(
                                                    'detail_page.review_note_label',
                                                    'Catatan tinjauan:',
                                                )}{' '}
                                                {data.review_notes}
                                            </p>
                                        )}
                                        {data.rejection_reason && (
                                            <p className="mt-4 rounded-md bg-destructive-soft p-3 text-sm text-destructive-foreground">
                                                {tPage(
                                                    'detail_page.reject_reason_label',
                                                    'Alasan ditolak:',
                                                )}{' '}
                                                {data.rejection_reason}
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>

                            <div className="space-y-4">
                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-base">
                                            {tPage(
                                                'detail_page.section_status',
                                                'Status',
                                            )}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        <VehicleLoanStatusBadge loan={data} />
                                        {data.is_overdue && (
                                            <p className="rounded-md bg-warning-soft p-2 text-xs font-medium text-warning">
                                                {tPage(
                                                    'detail_page.overdue_notice',
                                                    'Kendaraan melewati jadwal pengembalian.',
                                                )}
                                            </p>
                                        )}
                                        <dl className="space-y-2 text-sm">
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-muted-foreground">
                                                    {tPage(
                                                        'detail_page.submitted_label',
                                                        'Diajukan',
                                                    )}
                                                </dt>
                                                <dd>
                                                    {formatDateTimeByRegionalSettings(
                                                        data.submitted_at,
                                                    )}
                                                </dd>
                                            </div>
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-muted-foreground">
                                                    {tPage(
                                                        'detail_page.approved_label',
                                                        'Disetujui',
                                                    )}
                                                </dt>
                                                <dd>
                                                    {formatDateTimeByRegionalSettings(
                                                        data.approved_at,
                                                    )}
                                                </dd>
                                            </div>
                                        </dl>
                                        {action && (
                                            <Button className="w-full" asChild>
                                                <Link to={action.to}>
                                                    {action.label}
                                                </Link>
                                            </Button>
                                        )}
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-base">
                                            {tPage(
                                                'detail_page.approval_heading',
                                                'Persetujuan',
                                            )}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {!approval || steps.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                {tPage(
                                                    'detail_page.approval_empty',
                                                    'Belum ada langkah persetujuan.',
                                                )}
                                            </p>
                                        ) : (
                                            <ol className="space-y-4">
                                                {steps.map((step) => (
                                                    <li
                                                        key={step.step_order}
                                                        className="flex gap-3"
                                                    >
                                                        <span
                                                            className={`mt-1 flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold ${STEP_STATUS_CLASS[step.status] ?? 'bg-warning-soft text-warning'}`}
                                                        >
                                                            {step.step_order}
                                                        </span>
                                                        <div className="space-y-1">
                                                            <p className="text-sm font-medium">
                                                                {tPage(
                                                                    'detail_page.step_label',
                                                                    'Langkah {order} · {status}',
                                                                    {
                                                                        order: String(
                                                                            step.step_order,
                                                                        ),
                                                                        status: approvalStepStatusLabel(
                                                                            step.status,
                                                                            tPage,
                                                                        ),
                                                                    },
                                                                )}
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                {step.approver_name ??
                                                                    tPage(
                                                                        'detail_page.approver_fallback',
                                                                        'Penyetuju',
                                                                    )}
                                                                {step.acted_at
                                                                    ? ` · ${formatDateTimeByRegionalSettings(step.acted_at)}`
                                                                    : ''}
                                                            </p>
                                                            {step.comments && (
                                                                <p className="text-xs text-muted-foreground">
                                                                    “
                                                                    {
                                                                        step.comments
                                                                    }
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

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">
                                    {tPage(
                                        'detail_page.activity_heading',
                                        'Riwayat Aktivitas',
                                    )}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {!data.activity_logs?.length ? (
                                    <p className="text-sm text-muted-foreground">
                                        {tPage(
                                            'detail_page.activity_empty',
                                            'Belum ada aktivitas tercatat.',
                                        )}
                                    </p>
                                ) : (
                                    <ol className="space-y-3">
                                        {data.activity_logs.map((log) => (
                                            <li
                                                key={log.id}
                                                className="flex gap-3"
                                            >
                                                <span className="mt-1.5 size-2 shrink-0 rounded-full bg-muted-foreground" />
                                                <div>
                                                    <p className="text-sm font-medium">
                                                        {activityLabel(
                                                            log.event,
                                                            tPage,
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
                    </>
                )}
            </div>
        </AppLayout>
    );
}
