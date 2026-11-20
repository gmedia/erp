import { PageHeader } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { VehicleLoanQrPanel } from '@/components/vehicle-loans/VehicleLoanQrPanel';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
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
            toast.error('Lengkapi jadwal dan tujuan perjalanan.');
            return false;
        }

        if (new Date(form.end_at) <= new Date(form.start_at)) {
            toast.error('Waktu selesai harus setelah waktu mulai.');
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
            toast.success('Draft disimpan.');
            return id;
        } catch {
            toast.error('Gagal menyimpan draft.');
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
            toast.error('Draft tidak ditemukan. Simpan draft terlebih dahulu.');
            return;
        }

        setLoading(true);
        try {
            await axios.post(`/api/vehicle-loans/${id}/submit`);
            toast.success('Permintaan dikirim untuk persetujuan.');
            setForm(emptyForm);
            setDraftId(null);
        } catch {
            toast.error(
                'Gagal mengirim permintaan. Periksa kembali data Anda.',
            );
        } finally {
            setLoading(false);
        }
    }

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Peminjaman Kendaraan',
                    href: '/vehicle-loans/request',
                },
            ]}
        >
            <Helmet>
                <title>Permintaan Peminjaman Kendaraan</title>
            </Helmet>
            <div className="mx-auto max-w-2xl space-y-4 p-4 pb-24 md:p-6">
                <PageHeader
                    title="Permintaan Peminjaman Kendaraan"
                    description="Isi jadwal dan tujuan perjalanan, simpan draf, lalu kirim untuk persetujuan."
                />

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Jadwal</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="start_at">
                                Mulai
                                <span
                                    className="ml-1 text-destructive"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </Label>
                            <Input
                                id="start_at"
                                type="datetime-local"
                                lang="id-ID"
                                value={form.start_at}
                                onChange={(e) =>
                                    update('start_at', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="end_at">
                                Selesai
                                <span
                                    className="ml-1 text-destructive"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </Label>
                            <Input
                                id="end_at"
                                type="datetime-local"
                                lang="id-ID"
                                value={form.end_at}
                                onChange={(e) =>
                                    update('end_at', e.target.value)
                                }
                            />
                        </div>
                        <p className="text-xs text-muted-foreground sm:col-span-2">
                            Format: tanggal/bulan/tahun, gunakan waktu 24 jam.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Perjalanan</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-2">
                        <Label htmlFor="purpose">
                            Tujuan perjalanan
                            <span
                                className="ml-1 text-destructive"
                                aria-hidden="true"
                            >
                                *
                            </span>
                        </Label>
                        <Textarea
                            id="purpose"
                            value={form.purpose}
                            placeholder="Contoh: kunjungan klien ke Kantor Cabang Bandung"
                            onChange={(e) => update('purpose', e.target.value)}
                            rows={3}
                        />
                        <p className="text-xs text-muted-foreground">
                            Jelaskan tujuan singkat agar penyetuju mudah menilai
                            permintaan.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            Penugasan (opsional)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="driver_id">Pengemudi</Label>
                            <Select
                                value={form.driver_id || 'none'}
                                onValueChange={(value) =>
                                    update(
                                        'driver_id',
                                        value === 'none' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger id="driver_id">
                                    <SelectValue placeholder="Pilih driver" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Belum ditentukan (penyetuju melengkapi)
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
                        <div className="grid gap-2">
                            <Label htmlFor="vehicle_unit_id">
                                Unit kendaraan
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
                                <SelectTrigger id="vehicle_unit_id">
                                    <SelectValue placeholder="Pilih unit kendaraan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Belum ditentukan (penyetuju melengkapi)
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
                            Boleh dikosongkan. Approver dapat menetapkan driver
                            dan unit saat review.
                        </p>
                    </CardContent>
                </Card>

                <Card className="hidden sm:block">
                    <CardContent className="pt-6">
                        <VehicleLoanQrPanel form="request" />
                    </CardContent>
                </Card>

                <div className="sticky bottom-0 -mx-4 flex gap-2 border-t bg-background/95 p-4 backdrop-blur md:-mx-6 md:px-6">
                    <Button
                        variant="outline"
                        onClick={() => void saveDraft()}
                        disabled={loading}
                        className="min-h-11 flex-1"
                    >
                        Simpan Draf
                    </Button>
                    <Button
                        onClick={() => void submit()}
                        disabled={loading}
                        className="min-h-11 flex-1"
                    >
                        Kirim Permintaan
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
