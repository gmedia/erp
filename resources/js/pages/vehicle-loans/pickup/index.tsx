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
import { RequiredLabel } from '@/components/vehicle-loans/RequiredLabel';
import { VehicleLoanQrPanel } from '@/components/vehicle-loans/VehicleLoanQrPanel';
import { VehicleLoanSummaryCard } from '@/components/vehicle-loans/VehicleLoanSummaryCard';
import { submitVehicleLoanFormData } from '@/components/vehicle-loans/submitVehicleLoanFormData';
import { useVehicleLoanPageText } from '@/components/vehicle-loans/useVehicleLoanPageText';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { useToastTranslator } from '@/utils/i18nFallback';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { toast } from 'sonner';

interface Loan {
    id: number;
    code: string;
    start_at: string;
    end_at: string;
    purpose: string;
    requester_name: string | null;
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
    const tToast = useToastTranslator('vehicle_loan_toast');
    const tPage = useVehicleLoanPageText();

    async function loadApproved() {
        setLoading(true);
        try {
            const response = await axios.get(
                '/api/vehicle-loans?status=approved&per_page=50',
            );
            setLoans(response.data.data);
            setLoaded(true);
        } catch {
            toast.error(
                tToast('pickup_load_failed', 'Gagal memuat daftar permintaan.'),
            );
        } finally {
            setLoading(false);
        }
    }

    const selected = loans.find((loan) => String(loan.id) === loanId);

    async function submit() {
        if (!loanId || !photo) {
            toast.error(
                tToast(
                    'pickup_select_required',
                    'Pilih permintaan dan unggah foto.',
                ),
            );
            return;
        }

        const body = new FormData();
        body.append('photo_mode', photoMode);
        body.append('selfie_photo', photo);

        await submitVehicleLoanFormData({
            url: `/api/vehicle-loans/${loanId}/pickups`,
            body,
            setLoading,
            successMessage: tToast(
                'pickup_submitted',
                'Form pengambilan dikirim. Ambil kunci di pos keamanan.',
            ),
            failureMessage: tToast(
                'pickup_submit_failed',
                'Gagal mengirim form pengambilan.',
            ),
            onSuccess: () => {
                setLoanId('');
                setPhoto(null);
            },
        });
    }

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: tPage('request.breadcrumb', 'Peminjaman Kendaraan'),
                    href: '/vehicle-loans/request',
                },
                {
                    title: tPage('pickup.breadcrumb', 'Pengambilan'),
                    href: '/vehicle-loans/pickup',
                },
            ]}
        >
            <Helmet>
                <title>{tPage('pickup.title', 'Pengambilan Kendaraan')}</title>
            </Helmet>
            <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title={tPage('pickup.title', 'Pengambilan Kendaraan')}
                    description={tPage(
                        'pickup.description',
                        'Pilih permintaan yang sudah disetujui, unggah bukti, lalu ambil kunci di pos keamanan.',
                    )}
                />

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            {tPage('pickup.data_heading', 'Data Pengambilan')}
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
                                ? tPage('pickup.loading_button', 'Memuat...')
                                : tPage(
                                      'pickup.load_button',
                                      'Muat Permintaan Disetujui',
                                  )}
                        </Button>

                        <div className="grid gap-2">
                            <RequiredLabel htmlFor="loan">
                                {tPage('pickup.request_label', 'Permintaan')}
                            </RequiredLabel>
                            <Select
                                value={loanId || undefined}
                                onValueChange={setLoanId}
                                disabled={!loans.length}
                            >
                                <SelectTrigger id="loan">
                                    <SelectValue
                                        placeholder={
                                            loaded && !loans.length
                                                ? tPage(
                                                      'pickup.request_empty_placeholder',
                                                      'Tidak ada permintaan disetujui',
                                                  )
                                                : tPage(
                                                      'pickup.request_placeholder',
                                                      'Pilih permintaan',
                                                  )
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
                                                tPage(
                                                    'pickup.unit_undetermined',
                                                    'unit belum ditentukan',
                                                )}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {loaded && !loans.length && (
                                <p className="text-sm text-muted-foreground">
                                    {tPage(
                                        'pickup.request_empty_hint',
                                        'Belum ada permintaan yang disetujui. Ajukan permintaan terlebih dahulu.',
                                    )}
                                </p>
                            )}
                        </div>

                        {selected && <VehicleLoanSummaryCard loan={selected} />}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            {tPage('pickup.proof_heading', 'Foto Bukti')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="photo_mode">
                                {tPage('pickup.photo_mode_label', 'Jenis foto')}
                            </Label>
                            <Select
                                value={photoMode}
                                onValueChange={setPhotoMode}
                            >
                                <SelectTrigger id="photo_mode">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="with_driver">
                                        {tPage(
                                            'pickup.photo_mode_with_driver',
                                            'Selfie bersama pengemudi',
                                        )}
                                    </SelectItem>
                                    <SelectItem value="without_driver">
                                        {tPage(
                                            'pickup.photo_mode_without_driver',
                                            'Selfie dengan SIM (tanpa pengemudi)',
                                        )}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <RequiredLabel htmlFor="selfie">
                                {tPage('pickup.selfie_label', 'Foto selfie')}
                            </RequiredLabel>
                            <PhotoInput
                                id="selfie"
                                value={photo}
                                onChange={setPhoto}
                                buttonLabel={tPage(
                                    'pickup.selfie_button',
                                    'Ambil Selfie',
                                )}
                                hint={tPage(
                                    'pickup.selfie_hint',
                                    'Format JPG/PNG, maksimal 5 MB. Pastikan wajah dan SIM terlihat jelas.',
                                )}
                                capture
                            />
                        </div>
                        <Button
                            onClick={() => void submit()}
                            disabled={loading || !loanId || !photo}
                            className="min-h-11 w-full"
                        >
                            {tPage('pickup.submit_button', 'Kirim Pengambilan')}
                        </Button>
                        {!loanId && (
                            <p className="text-center text-xs text-muted-foreground">
                                {tPage(
                                    'pickup.select_first_hint',
                                    'Pilih permintaan terlebih dahulu.',
                                )}
                            </p>
                        )}
                        {loanId && !photo && (
                            <p className="text-center text-xs text-muted-foreground">
                                {tPage(
                                    'pickup.upload_first_hint',
                                    'Unggah foto untuk mengirim.',
                                )}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card className="hidden sm:block">
                    <CardContent className="pt-6">
                        <VehicleLoanQrPanel form="pickup" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
