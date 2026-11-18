import { PageHeader } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { QRCodeSVG } from 'qrcode.react';
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
    const [qrUrl] = useState(
        () => `${window.location.origin}/vehicle-loans/request`,
    );

    function update(key: keyof LoanForm, value: string) {
        setForm((current) => ({ ...current, [key]: value }));
    }

    async function saveDraft() {
        setLoading(true);
        try {
            const response = await axios.post('/api/vehicle-loans', form);
            setDraftId(response.data.id);
            toast.success('Draft disimpan.');
        } catch {
            toast.error('Gagal menyimpan draft.');
        } finally {
            setLoading(false);
        }
    }

    async function submit() {
        if (!draftId) {
            await saveDraft();
        }

        const id =
            draftId ??
            (await axios.get('/api/vehicle-loans/my?per_page=1')).data.data[0]
                ?.id;

        if (!id) {
            toast.error('Draft tidak ditemukan. Simpan draft terlebih dahulu.');
            return;
        }

        setLoading(true);
        try {
            await axios.post(`/api/vehicle-loans/${id}/submit`);
            toast.success('Permintaan dikirim untuk approval.');
            setForm(emptyForm);
            setDraftId(null);
        } catch {
            toast.error('Gagal mengirim permintaan. Lengkapi semua field.');
        } finally {
            setLoading(false);
        }
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Vehicle Loans', href: '/vehicle-loans/request' },
            ]}
        >
            <Helmet>
                <title>Permintaan Peminjaman Kendaraan </title>
            </Helmet>
            <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title="Permintaan Peminjaman Kendaraan"
                    description="Isi form berikut, simpan draft, lalu kirim untuk approval."
                />

                <Card>
                    <CardContent className="space-y-4 pt-4">
                        <div className="grid gap-2">
                            <Label htmlFor="start_at">
                                Mulai (tanggal & jam)
                            </Label>
                            <Input
                                id="start_at"
                                type="datetime-local"
                                value={form.start_at}
                                onChange={(e) =>
                                    update('start_at', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="end_at">
                                Selesai (tanggal & jam)
                            </Label>
                            <Input
                                id="end_at"
                                type="datetime-local"
                                value={form.end_at}
                                onChange={(e) =>
                                    update('end_at', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="purpose">Tujuan perjalanan</Label>
                            <Textarea
                                id="purpose"
                                value={form.purpose}
                                onChange={(e) =>
                                    update('purpose', e.target.value)
                                }
                                rows={3}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="driver_id">
                                Driver (opsional — diisi approver bila kosong)
                            </Label>
                            <Input
                                id="driver_id"
                                inputMode="numeric"
                                placeholder="ID driver"
                                value={form.driver_id}
                                onChange={(e) =>
                                    update('driver_id', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="vehicle_unit_id">
                                Unit kendaraan (opsional)
                            </Label>
                            <Input
                                id="vehicle_unit_id"
                                inputMode="numeric"
                                placeholder="ID unit"
                                value={form.vehicle_unit_id}
                                onChange={(e) =>
                                    update('vehicle_unit_id', e.target.value)
                                }
                            />
                        </div>
                        <div className="sticky bottom-0 flex gap-2 bg-background pt-2">
                            <Button
                                variant="outline"
                                onClick={saveDraft}
                                disabled={loading}
                                className="flex-1"
                            >
                                Simpan Draft
                            </Button>
                            <Button
                                onClick={submit}
                                disabled={loading}
                                className="flex-1"
                            >
                                Kirim Permintaan
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="flex items-center gap-4 pt-4">
                        <QRCodeSVG value={qrUrl} size={96} />
                        <p className="text-xs text-muted-foreground">
                            QR halaman ini — ditempel di loket. Scan + login
                            untuk mengisi permintaan.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
