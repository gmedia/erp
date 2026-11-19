import { PageHeader } from '@/components/common/PageHeader';
import { PhotoInput } from '@/components/common/PhotoInput';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { VehicleLoanQrPanel } from '@/components/vehicle-loans/VehicleLoanQrPanel';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { toast } from 'sonner';

interface Loan {
    id: number;
    code: string;
    start_at: string;
    end_at: string;
    purpose: string;
    plate_number: string | null;
    driver_name: string | null;
    status: string;
}

export default function VehicleLoanPickupPage() {
    const [loans, setLoans] = useState<Loan[]>([]);
    const [loanId, setLoanId] = useState('');
    const [photoMode, setPhotoMode] = useState('with_driver');
    const [photo, setPhoto] = useState<File | null>(null);
    const [loading, setLoading] = useState(false);

    async function loadApproved() {
        try {
            const response = await axios.get(
                '/api/vehicle-loans?status=approved&per_page=50',
            );
            setLoans(response.data.data);
        } catch {
            toast.error('Gagal memuat daftar permintaan.');
        }
    }

    const selected = loans.find((loan) => String(loan.id) === loanId);

    async function submit() {
        if (!loanId || !photo) {
            toast.error('Pilih permintaan dan unggah foto.');
            return;
        }

        const body = new FormData();
        body.append('photo_mode', photoMode);
        body.append('selfie_photo', photo);

        setLoading(true);
        try {
            await axios.post(`/api/vehicle-loans/${loanId}/pickups`, body);
            toast.success(
                'Form pengambilan dikirim. Ambil kunci di pos security.',
            );
            setLoanId('');
            setPhoto(null);
        } catch {
            toast.error('Gagal mengirim form pengambilan.');
        } finally {
            setLoading(false);
        }
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pengambilan', href: '/vehicle-loans/pickup' },
            ]}
        >
            <Helmet>
                <title>Pengambilan Kendaraan </title>
            </Helmet>
            <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title="Form Pengambilan Kendaraan"
                    description="Pilih permintaan yang sudah disetujui, unggah foto, lalu ambil kunci di security."
                />

                <Card>
                    <CardContent className="space-y-4 pt-4">
                        <Button
                            variant="outline"
                            onClick={loadApproved}
                            className="w-full"
                        >
                            Muat permintaan disetujui
                        </Button>

                        <div className="grid gap-2">
                            <Label htmlFor="loan">Permintaan</Label>
                            <Input
                                id="loan"
                                list="approved-loans"
                                value={loanId}
                                onChange={(e) => setLoanId(e.target.value)}
                                placeholder="Pilih nomor permintaan"
                            />
                            <datalist id="approved-loans">
                                {loans.map((loan) => (
                                    <option key={loan.id} value={loan.id}>
                                        {loan.code} —{' '}
                                        {loan.plate_number ??
                                            'unit belum ditentukan'}
                                    </option>
                                ))}
                            </datalist>
                        </div>

                        {selected && (
                            <div className="rounded-md bg-muted p-3 text-sm">
                                <p>
                                    <strong>{selected.code}</strong>
                                </p>
                                <p>Driver: {selected.driver_name ?? '-'}</p>
                                <p>Unit: {selected.plate_number ?? '-'}</p>
                                <p>Tujuan: {selected.purpose}</p>
                                <p>
                                    Mulai:{' '}
                                    {new Date(
                                        selected.start_at,
                                    ).toLocaleString()}
                                </p>
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="photo_mode">Jenis foto</Label>
                            <select
                                id="photo_mode"
                                className="flex h-10 w-full rounded-md border bg-background px-3 text-sm"
                                value={photoMode}
                                onChange={(e) => setPhotoMode(e.target.value)}
                            >
                                <option value="with_driver">
                                    Selfie bersama driver
                                </option>
                                <option value="without_driver">
                                    Selfie dengan SIM (tanpa driver)
                                </option>
                            </select>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="selfie">Foto</Label>
                            <PhotoInput
                                id="selfie"
                                value={photo}
                                onChange={setPhoto}
                            />
                        </div>

                        <Button
                            onClick={submit}
                            disabled={loading}
                            className="w-full"
                        >
                            Kirim Form Pengambilan
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-4">
                        <VehicleLoanQrPanel form="pickup" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
