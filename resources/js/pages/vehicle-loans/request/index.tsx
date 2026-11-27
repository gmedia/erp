import { PageHeader } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { RequiredLabel } from '@/components/vehicle-loans/RequiredLabel';
import { useVehicleLoanPageText } from '@/components/vehicle-loans/useVehicleLoanPageText';
import { VehicleLoanDateTimeFields } from '@/components/vehicle-loans/VehicleLoanDateTimeFields';
import { VehicleLoanQrPanel } from '@/components/vehicle-loans/VehicleLoanQrPanel';
import { usePermissions } from '@/hooks/usePermissions';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { useToastTranslator } from '@/utils/i18nFallback';
import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { toast } from 'sonner';

interface LoanForm {
    start_at: string;
    end_at: string;
    purpose: string;
    driver_id: string;
    vehicle_unit_id: string;
}

interface AssignmentOptions {
    drivers: Array<{ id: number; name: string; license_expiry: string | null }>;
    units: Array<{ id: number; plate_number: string; vehicle_type: string }>;
}

const emptyForm: LoanForm = {
    start_at: '',
    end_at: '',
    purpose: '',
    driver_id: '',
    vehicle_unit_id: '',
};

export default function VehicleLoanRequestPage() {
    const [form, setForm] = useState<LoanForm>(emptyForm);
    const [draftId, setDraftId] = useState<number | null>(null);
    const [loading, setLoading] = useState(false);
    const { can } = usePermissions();
    const canRequest = can('vehicle_loan.request');
    const tToast = useToastTranslator('vehicle_loan_toast');
    const tPage = useVehicleLoanPageText();

    const { data: options } = useQuery({
        queryKey: ['vehicle-loans', 'assignment-options'],
        queryFn: async () => {
            const response = await axios.get<AssignmentOptions>(
                '/api/vehicle-loans/assignment-options',
            );
            return response.data;
        },
        staleTime: 5 * 60 * 1000,
    });

    function update(key: keyof LoanForm, value: string) {
        setForm((current) => ({ ...current, [key]: value }));
    }

    function validate(): boolean {
        if (!form.start_at || !form.end_at || form.purpose.trim().length < 5) {
            toast.error(
                tToast(
                    'request_schedule_incomplete',
                    'Lengkapi jadwal dan tujuan perjalanan.',
                ),
            );
            return false;
        }

        if (new Date(form.end_at) <= new Date(form.start_at)) {
            toast.error(
                tToast(
                    'request_end_before_start',
                    'Waktu selesai harus setelah waktu mulai.',
                ),
            );
            return false;
        }

        return true;
    }

    async function saveDraft(): Promise<number | null> {
        setLoading(true);
        try {
            const response = await axios.post('/api/vehicle-loans', form);
            const id = Number(response.data.id ?? response.data.data?.id);
            setDraftId(id);
            toast.success(tToast('request_draft_saved', 'Draft disimpan.'));
            return id;
        } catch {
            toast.error(
                tToast('request_draft_failed', 'Gagal menyimpan draft.'),
            );
            return null;
        } finally {
            setLoading(false);
        }
    }

    async function submit() {
        if (!validate()) {
            return;
        }

        const id = draftId ?? (await saveDraft());

        if (!id) {
            toast.error(
                tToast(
                    'request_draft_missing',
                    'Draft tidak ditemukan. Simpan draft terlebih dahulu.',
                ),
            );
            return;
        }

        setLoading(true);
        try {
            await axios.post(`/api/vehicle-loans/${id}/submit`);
            toast.success(
                tToast(
                    'request_submitted',
                    'Permintaan dikirim untuk persetujuan.',
                ),
            );
            setForm(emptyForm);
            setDraftId(null);
        } catch {
            toast.error(
                tToast(
                    'request_submit_failed',
                    'Gagal mengirim permintaan. Periksa kembali data Anda.',
                ),
            );
        } finally {
            setLoading(false);
        }
    }

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: tPage('request.breadcrumb', 'Peminjaman Kendaraan'),
                    href: '/vehicle-loans/request',
                },
                {
                    title: tPage('request.breadcrumb_leaf', 'Permintaan'),
                    href: '/vehicle-loans/request',
                },
            ]}
        >
            <Helmet>
                <title>
                    {tPage('request.title', 'Permintaan Peminjaman Kendaraan')}
                </title>
            </Helmet>
            <div className="mx-auto max-w-2xl space-y-4 p-4 pb-24 md:p-6">
                <PageHeader
                    title={tPage(
                        'request.title',
                        'Permintaan Peminjaman Kendaraan',
                    )}
                    description={tPage(
                        'request.description',
                        'Isi jadwal dan tujuan perjalanan, simpan draf, lalu kirim untuk persetujuan.',
                    )}
                />

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            {tPage('request.schedule_heading', 'Jadwal')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <VehicleLoanDateTimeFields
                            id="start_at"
                            label={tPage('request.start_label', 'Mulai')}
                            value={form.start_at}
                            onChange={(value) => update('start_at', value)}
                            required
                        />
                        <VehicleLoanDateTimeFields
                            id="end_at"
                            label={tPage('request.end_label', 'Selesai')}
                            value={form.end_at}
                            onChange={(value) => update('end_at', value)}
                            required
                        />
                        <p className="text-xs text-muted-foreground sm:col-span-2">
                            {tPage(
                                'request.schedule_format_hint',
                                'Format: tanggal/bulan/tahun, gunakan waktu 24 jam.',
                            )}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            {tPage('request.trip_heading', 'Perjalanan')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-2">
                        <RequiredLabel htmlFor="purpose">
                            {tPage(
                                'request.purpose_label',
                                'Tujuan perjalanan',
                            )}
                        </RequiredLabel>
                        <Textarea
                            id="purpose"
                            value={form.purpose}
                            placeholder={tPage(
                                'request.purpose_placeholder',
                                'Contoh: kunjungan klien',
                            )}
                            onChange={(e) => update('purpose', e.target.value)}
                            rows={3}
                        />
                        <p className="text-xs text-muted-foreground">
                            {tPage(
                                'request.purpose_hint',
                                'Jelaskan tujuan singkat agar penyetuju mudah menilai permintaan.',
                            )}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            {tPage(
                                'request.assignment_heading',
                                'Penugasan (opsional)',
                            )}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="driver_id">
                                {tPage('request.driver_label', 'Pengemudi')}
                            </Label>
                            <Select
                                value={form.driver_id || 'none'}
                                onValueChange={(value) =>
                                    update(
                                        'driver_id',
                                        value === 'none' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="driver_id"
                                    className="min-w-0 text-left"
                                    title={
                                        form.driver_id
                                            ? undefined
                                            : tPage(
                                                  'request.undecided_option',
                                                  'Belum ditentukan (penyetuju melengkapi)',
                                              )
                                    }
                                >
                                    <SelectValue
                                        className="min-w-0 truncate"
                                        placeholder={tPage(
                                            'request.driver_placeholder',
                                            'Pilih pengemudi',
                                        )}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        value="none"
                                        title={tPage(
                                            'request.undecided_option',
                                            'Belum ditentukan (penyetuju melengkapi)',
                                        )}
                                    >
                                        {tPage(
                                            'request.undecided_option',
                                            'Belum ditentukan (penyetuju melengkapi)',
                                        )}
                                    </SelectItem>
                                    {options?.drivers.map((driver) => (
                                        <SelectItem
                                            key={driver.id}
                                            value={String(driver.id)}
                                        >
                                            {driver.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="vehicle_unit_id">
                                {tPage('request.unit_label', 'Unit kendaraan')}
                            </Label>
                            <Select
                                value={form.vehicle_unit_id || 'none'}
                                onValueChange={(value) =>
                                    update(
                                        'vehicle_unit_id',
                                        value === 'none' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="vehicle_unit_id"
                                    className="min-w-0 text-left"
                                    title={
                                        form.vehicle_unit_id
                                            ? undefined
                                            : tPage(
                                                  'request.undecided_option',
                                                  'Belum ditentukan (penyetuju melengkapi)',
                                              )
                                    }
                                >
                                    <SelectValue
                                        className="min-w-0 truncate"
                                        placeholder={tPage(
                                            'request.unit_placeholder',
                                            'Pilih unit kendaraan',
                                        )}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        value="none"
                                        title={tPage(
                                            'request.undecided_option',
                                            'Belum ditentukan (penyetuju melengkapi)',
                                        )}
                                    >
                                        {tPage(
                                            'request.undecided_option',
                                            'Belum ditentukan (penyetuju melengkapi)',
                                        )}
                                    </SelectItem>
                                    {options?.units.map((unit) => (
                                        <SelectItem
                                            key={unit.id}
                                            value={String(unit.id)}
                                        >
                                            {unit.plate_number} (
                                            {unit.vehicle_type})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <p className="text-xs text-muted-foreground sm:col-span-2">
                            {tPage(
                                'request.assignment_hint',
                                'Boleh dikosongkan. Penyetuju dapat menetapkan pengemudi dan unit saat peninjauan.',
                            )}
                        </p>
                    </CardContent>
                </Card>

                <Card className="hidden sm:block">
                    <CardContent className="pt-6">
                        <VehicleLoanQrPanel form="request" />
                    </CardContent>
                </Card>

                {canRequest && (
                    <div
                        className={[
                            'sticky bottom-0 -mx-4 flex gap-2 border-t',
                            'bg-background/95 p-4 backdrop-blur md:-mx-6 md:px-6',
                        ].join(' ')}
                    >
                        <Button
                            variant="outline"
                            onClick={() => void saveDraft()}
                            disabled={loading}
                            className="min-h-11 flex-1"
                        >
                            {tPage('request.save_draft_button', 'Simpan Draf')}
                        </Button>
                        <Button
                            onClick={() => void submit()}
                            disabled={loading}
                            className="min-h-11 flex-1"
                        >
                            {tPage('request.submit_button', 'Kirim Permintaan')}
                        </Button>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
