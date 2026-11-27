import { PageHeader } from '@/components/common/PageHeader';
import { PhotoInput } from '@/components/common/PhotoInput';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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

interface PickupOption {
    id: number;
    vehicle_loan_id: number;
    code: string;
    plate_number: string | null;
    requester_name: string | null;
    driver_name: string | null;
    purpose: string | null;
    start_at: string | null;
    end_at: string | null;
}

export default function VehicleLoanReturnPage() {
    const [pickups, setPickups] = useState<PickupOption[]>([]);
    const [pickupId, setPickupId] = useState('');
    const [handoverPhoto, setHandoverPhoto] = useState<File | null>(null);
    const [conditionPhoto, setConditionPhoto] = useState<File | null>(null);
    const [incident, setIncident] = useState(false);
    const [incidentNote, setIncidentNote] = useState('');
    const [odometerEnd, setOdometerEnd] = useState('');
    const [loading, setLoading] = useState(false);
    const [loaded, setLoaded] = useState(false);
    const tToast = useToastTranslator('vehicle_loan_toast');
    const tPage = useVehicleLoanPageText();

    async function loadPickups() {
        setLoading(true);
        try {
            const response = await axios.get(
                '/api/vehicle-loans/my?per_page=50',
            );
            const items = (
                response.data.data as Array<{
                    code: string;
                    plate_number: string | null;
                    requester_name: string | null;
                    driver_name: string | null;
                    purpose: string | null;
                    start_at: string | null;
                    end_at: string | null;
                    pickup?: {
                        id: number;
                        vehicle_loan_id: number;
                        key_handover_at?: string | null;
                    };
                }>
            )
                .filter((loan) => loan.pickup?.key_handover_at)
                .map((loan) => ({
                    id: loan.pickup!.id,
                    vehicle_loan_id: loan.pickup!.vehicle_loan_id,
                    code: loan.code,
                    plate_number: loan.plate_number,
                    requester_name: loan.requester_name,
                    driver_name: loan.driver_name,
                    purpose: loan.purpose,
                    start_at: loan.start_at,
                    end_at: loan.end_at,
                }));
            setPickups(items);
            setLoaded(true);
        } catch {
            toast.error(
                tToast('return_load_failed', 'Gagal memuat data pengambilan.'),
            );
        } finally {
            setLoading(false);
        }
    }

    const selected = pickups.find((pickup) => String(pickup.id) === pickupId);

    async function submit() {
        if (!pickupId || !handoverPhoto || !conditionPhoto) {
            toast.error(
                tToast(
                    'return_incomplete',
                    'Lengkapi pengambilan dan kedua foto.',
                ),
            );
            return;
        }

        if (incident && incidentNote.trim().length < 5) {
            toast.error(
                tToast(
                    'return_incident_note_short',
                    'Isi keterangan insiden minimal 5 karakter.',
                ),
            );
            return;
        }

        const body = new FormData();
        body.append('handover_photo', handoverPhoto);
        body.append('condition_photo', conditionPhoto);
        body.append('incident', incident ? '1' : '0');
        if (incident) body.append('incident_note', incidentNote);
        if (odometerEnd) body.append('odometer_end', odometerEnd);

        await submitVehicleLoanFormData({
            url: `/api/vehicle-loans/pickups/${pickupId}/returns`,
            body,
            setLoading,
            successMessage: tToast(
                'return_recorded',
                'Pengembalian berhasil dicatat.',
            ),
            failureMessage: tToast(
                'return_failed',
                'Gagal mencatat pengembalian.',
            ),
            onSuccess: () => {
                setPickupId('');
                setHandoverPhoto(null);
                setConditionPhoto(null);
                setIncident(false);
                setIncidentNote('');
                setOdometerEnd('');
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
                    title: tPage('return.breadcrumb', 'Pengembalian'),
                    href: '/vehicle-loans/return',
                },
            ]}
        >
            <Helmet>
                <title>{tPage('return.title', 'Pengembalian Kendaraan')}</title>
            </Helmet>
            <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title={tPage('return.title', 'Pengembalian Kendaraan')}
                    description={tPage(
                        'return.description',
                        'Pilih pengambilan, unggah bukti serah terima dan kondisi kendaraan, ' +
                            'lalu catat insiden bila ada.',
                    )}
                />

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            {tPage('return.data_heading', 'Data Pengembalian')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Button
                            variant="outline"
                            onClick={() => void loadPickups()}
                            disabled={loading}
                            className="min-h-11 w-full"
                        >
                            {loading
                                ? tPage('return.loading_button', 'Memuat...')
                                : tPage(
                                      'return.load_button',
                                      'Muat Data Pengambilan Saya',
                                  )}
                        </Button>

                        <div className="grid gap-2">
                            <RequiredLabel htmlFor="pickup">
                                {tPage('return.pickup_label', 'Pengambilan')}
                            </RequiredLabel>
                            <Select
                                value={pickupId || undefined}
                                onValueChange={setPickupId}
                                disabled={!pickups.length}
                            >
                                <SelectTrigger id="pickup">
                                    <SelectValue
                                        placeholder={
                                            loaded && !pickups.length
                                                ? tPage(
                                                      'return.pickup_empty_placeholder',
                                                      'Tidak ada pengambilan aktif',
                                                  )
                                                : tPage(
                                                      'return.pickup_placeholder',
                                                      'Pilih pengambilan',
                                                  )
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {pickups.map((pickup) => (
                                        <SelectItem
                                            key={pickup.id}
                                            value={String(pickup.id)}
                                        >
                                            {pickup.code} —{' '}
                                            {pickup.plate_number ??
                                                tPage(
                                                    'return.unit_undetermined',
                                                    'unit belum ditentukan',
                                                )}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {loaded && !pickups.length && (
                                <p className="text-sm text-muted-foreground">
                                    {tPage(
                                        'return.pickup_empty_hint',
                                        'Belum ada kendaraan yang sedang dipinjam. ' +
                                            'Pengembalian tersedia setelah kunci diserahkan petugas keamanan.',
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
                            {tPage(
                                'return.evidence_heading',
                                'Bukti & Kondisi',
                            )}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-2">
                            <RequiredLabel htmlFor="handover">
                                {tPage(
                                    'return.handover_photo_label',
                                    'Foto bukti serah terima dengan petugas keamanan',
                                )}
                            </RequiredLabel>
                            <PhotoInput
                                id="handover"
                                value={handoverPhoto}
                                onChange={setHandoverPhoto}
                                buttonLabel={tPage(
                                    'return.handover_photo_button',
                                    'Ambil Foto Serah Terima',
                                )}
                                hint={tPage(
                                    'return.handover_photo_hint',
                                    'Foto bersama petugas keamanan saat mengembalikan kunci. JPG/PNG, maks. 5 MB.',
                                )}
                                capture
                            />
                        </div>
                        <div className="grid gap-2">
                            <RequiredLabel htmlFor="condition">
                                {tPage(
                                    'return.condition_photo_label',
                                    'Foto kondisi kendaraan',
                                )}
                            </RequiredLabel>
                            <PhotoInput
                                id="condition"
                                value={conditionPhoto}
                                onChange={setConditionPhoto}
                                buttonLabel={tPage(
                                    'return.condition_photo_button',
                                    'Ambil Foto Kondisi',
                                )}
                                hint={tPage(
                                    'return.condition_photo_hint',
                                    'Ambil satu foto yang menunjukkan kondisi keseluruhan kendaraan.',
                                )}
                                capture
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="odometer">
                                {tPage(
                                    'return.odometer_label',
                                    'Odometer akhir (opsional)',
                                )}
                            </Label>
                            <div className="relative">
                                <Input
                                    id="odometer"
                                    inputMode="numeric"
                                    value={odometerEnd}
                                    onChange={(e) =>
                                        setOdometerEnd(e.target.value)
                                    }
                                    className="pr-10"
                                />
                                <span className="absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground">
                                    {tPage('return.odometer_unit', 'km')}
                                </span>
                            </div>
                        </div>
                        <div className="flex min-h-11 items-center gap-2 text-sm">
                            <Checkbox
                                id="incident"
                                checked={incident}
                                onCheckedChange={(checked) =>
                                    setIncident(checked === true)
                                }
                            />
                            <Label htmlFor="incident" className="font-normal">
                                {tPage(
                                    'return.incident_label',
                                    'Ada insiden selama peminjaman',
                                )}
                            </Label>
                        </div>
                        {incident && (
                            <div className="grid gap-2">
                                <RequiredLabel htmlFor="incident_note">
                                    {tPage(
                                        'return.incident_note_label',
                                        'Keterangan insiden',
                                    )}
                                </RequiredLabel>
                                <Textarea
                                    id="incident_note"
                                    value={incidentNote}
                                    onChange={(e) =>
                                        setIncidentNote(e.target.value)
                                    }
                                    rows={3}
                                    placeholder={tPage(
                                        'return.incident_note_placeholder',
                                        'Jelaskan kejadian dan kondisi kendaraan',
                                    )}
                                />
                            </div>
                        )}
                        <Button
                            onClick={() => void submit()}
                            disabled={
                                loading ||
                                !pickupId ||
                                !handoverPhoto ||
                                !conditionPhoto
                            }
                            className="min-h-11 w-full"
                        >
                            {tPage(
                                'return.submit_button',
                                'Kirim Pengembalian',
                            )}
                        </Button>
                        {!pickupId && (
                            <p className="text-center text-xs text-muted-foreground">
                                {tPage(
                                    'return.select_first_hint',
                                    'Pilih data pengembalian terlebih dahulu.',
                                )}
                            </p>
                        )}
                        {pickupId && (!handoverPhoto || !conditionPhoto) && (
                            <p className="text-center text-xs text-muted-foreground">
                                {tPage(
                                    'return.upload_both_hint',
                                    'Unggah kedua foto untuk mengirim.',
                                )}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card className="hidden sm:block">
                    <CardContent className="pt-6">
                        <VehicleLoanQrPanel form="return" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
