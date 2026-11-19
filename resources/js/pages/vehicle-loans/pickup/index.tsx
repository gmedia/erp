import { PageHeader } from '@/components/common/PageHeader';
import { PhotoInput } from '@/components/common/PhotoInput';
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
import { VehicleLoanQrPanel } from '@/components/vehicle-loans/VehicleLoanQrPanel';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
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
    const [loaded, setLoaded] = useState(false);

    async function loadApproved() {
        setLoading(true);
        try {
            const response = await axios.get(
                '/api/vehicle-loans?status=approved&per_page=50',
            );
            setLoans(response.data.data);
            setLoaded(true);
        } catch {
            toast.error('Gagal memuat daftar permintaan.');
        } finally {
            setLoading(false);
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
                <title>Pengambilan Kendaraan</title>
            </Helmet>
            <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title="Pengambilan Kendaraan"
                    description="Pilih permintaan yang sudah disetujui, unggah bukti, lalu ambil kunci di pos security."
                />

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            Data Pengambilan
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Button
                            variant="outline"
                            onClick={() => void loadApproved()}
                            disabled={loading}
                            className="min-h-11 w-full"
                        >
                            {loading
                                ? 'Memuat...'
                                : 'Muat Permintaan Disetujui'}
                        </Button>

                        <div className="grid gap-2">
                            <Label htmlFor="loan">
                                Permintaan
                                <span
                                    className="ml-1 text-destructive"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </Label>
                            <Select
                                value={loanId || undefined}
                                onValueChange={setLoanId}
                                disabled={!loans.length}
                            >
                                <SelectTrigger id="loan">
                                    <SelectValue
                                        placeholder={
                                            loaded && !loans.length
                                                ? 'Tidak ada permintaan disetujui'
                                                : 'Pilih permintaan'
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {loans.map((loan) => (
                                        <SelectItem
                                            key={loan.id}
                                            value={String(loan.id)}
                                        >
                                            {loan.code} —{' '}
                                            {loan.plate_number ??
                                                'unit belum ditentukan'}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {loaded && !loans.length && (
                                <p className="text-sm text-muted-foreground">
                                    Belum ada permintaan yang disetujui. Ajukan
                                    permintaan terlebih dahulu.
                                </p>
                            )}
                        </div>

                        {selected && (
                            <div className="space-y-1 rounded-lg border bg-muted/30 p-3 text-sm">
                                <p className="font-medium">{selected.code}</p>
                                <p>Driver: {selected.driver_name ?? '-'}</p>
                                <p>Unit: {selected.plate_number ?? '-'}</p>
                                <p>Tujuan: {selected.purpose}</p>
                                <p>
                                    Mulai:{' '}
                                    {formatDateTimeByRegionalSettings(
                                        selected.start_at,
                                    )}
                                </p>
                                <p>
                                    Selesai:{' '}
                                    {formatDateTimeByRegionalSettings(
                                        selected.end_at,
                                    )}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Foto Bukti</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="photo_mode">Jenis foto</Label>
                            <Select
                                value={photoMode}
                                onValueChange={setPhotoMode}
                            >
                                <SelectTrigger id="photo_mode">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="with_driver">
                                        Selfie bersama driver
                                    </SelectItem>
                                    <SelectItem value="without_driver">
                                        Selfie dengan SIM (tanpa driver)
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="selfie">
                                Foto selfie
                                <span
                                    className="ml-1 text-destructive"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </Label>
                            <PhotoInput
                                id="selfie"
                                value={photo}
                                onChange={setPhoto}
                                buttonLabel="Ambil Selfie"
                                hint="Format JPG/PNG, maksimal 5 MB. Pastikan wajah dan SIM terlihat jelas."
                                capture
                            />
                        </div>
                        <Button
                            onClick={() => void submit()}
                            disabled={loading || !loanId || !photo}
                            className="min-h-11 w-full"
                        >
                            Kirim Pengambilan
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <VehicleLoanQrPanel form="pickup" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
