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
        const id = draftId ?? (await saveDraft());

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
                            <Label htmlFor="driver_id">Driver (opsional)</Label>
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
                                    <SelectValue placeholder="Kosong — diisi approver" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Kosong — diisi approver
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
                                Unit kendaraan (opsional)
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
                                    <SelectValue placeholder="Kosong — diisi approver" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Kosong — diisi approver
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
                        <div className="sticky bottom-0 flex gap-2 bg-background pt-2">
                            <Button
                                variant="outline"
                                onClick={() => void saveDraft()}
                                disabled={loading}
                                className="flex-1"
                            >
                                Simpan Draft
                            </Button>
                            <Button
                                onClick={() => void submit()}
                                disabled={loading}
                                className="flex-1"
                            >
                                Kirim Permintaan
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-4">
                        <VehicleLoanQrPanel form="request" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
