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
import { VehicleLoanQrPanel } from '@/components/vehicle-loans/VehicleLoanQrPanel';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
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
            toast.error('Gagal memuat data pengambilan.');
        } finally {
            setLoading(false);
        }
    }

    const selected = pickups.find((pickup) => String(pickup.id) === pickupId);

    async function submit() {
        if (!pickupId || !handoverPhoto || !conditionPhoto) {
            toast.error('Lengkapi pengambilan dan kedua foto.');
            return;
        }

        if (incident && incidentNote.trim().length < 5) {
            toast.error('Isi keterangan insiden minimal 5 karakter.');
            return;
        }

        const body = new FormData();
        body.append('handover_photo', handoverPhoto);
        body.append('condition_photo', conditionPhoto);
        body.append('incident', incident ? '1' : '0');
        if (incident) body.append('incident_note', incidentNote);
        if (odometerEnd) body.append('odometer_end', odometerEnd);

        setLoading(true);
        try {
            await axios.post(
                `/api/vehicle-loans/pickups/${pickupId}/returns`,
                body,
            );
            toast.success('Pengembalian berhasil dicatat.');
            setPickupId('');
            setHandoverPhoto(null);
            setConditionPhoto(null);
            setIncident(false);
            setIncidentNote('');
            setOdometerEnd('');
        } catch {
            toast.error('Gagal mencatat pengembalian.');
        } finally {
            setLoading(false);
        }
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pengembalian', href: '/vehicle-loans/return' },
            ]}
        >
            <Helmet>
                <title>Pengembalian Kendaraan</title>
            </Helmet>
            <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title="Pengembalian Kendaraan"
                    description="Pilih pengambilan, unggah bukti serah terima dan kondisi kendaraan, lalu catat insiden bila ada."
                />

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            Data Pengembalian
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
                                ? 'Memuat...'
                                : 'Muat Data Pengambilan Saya'}
                        </Button>

                        <div className="grid gap-2">
                            <Label htmlFor="pickup">
                                Pengambilan
                                <span
                                    className="ml-1 text-destructive"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </Label>
                            <Select
                                value={pickupId || undefined}
                                onValueChange={setPickupId}
                                disabled={!pickups.length}
                            >
                                <SelectTrigger id="pickup">
                                    <SelectValue
                                        placeholder={
                                            loaded && !pickups.length
                                                ? 'Tidak ada pengambilan aktif'
                                                : 'Pilih pengambilan'
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
                                                'unit belum ditentukan'}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {loaded && !pickups.length && (
                                <p className="text-sm text-muted-foreground">
                                    Belum ada kendaraan yang sedang dipinjam.
                                    Pengembalian tersedia setelah kunci
                                    diserahkan security.
                                </p>
                            )}
                        </div>

                        {selected && (
                            <div className="space-y-1 rounded-lg border bg-muted/30 p-3 text-sm">
                                <p className="font-medium">{selected.code}</p>
                                <p>
                                    Peminjam: {selected.requester_name ?? '-'}
                                </p>
                                <p>Pengemudi: {selected.driver_name ?? '-'}</p>
                                <p>Kendaraan: {selected.plate_number ?? '-'}</p>
                                <p>Tujuan: {selected.purpose ?? '-'}</p>
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
                        <CardTitle className="text-base">
                            Bukti & Kondisi
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="handover">
                                Foto bukti serah terima dengan petugas keamanan
                                <span
                                    className="ml-1 text-destructive"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </Label>
                            <PhotoInput
                                id="handover"
                                value={handoverPhoto}
                                onChange={setHandoverPhoto}
                                buttonLabel="Ambil Foto Serah Terima"
                                hint="Foto bersama petugas keamanan saat mengembalikan kunci. JPG/PNG, maks. 5 MB."
                                capture
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="condition">
                                Foto kondisi kendaraan
                                <span
                                    className="ml-1 text-destructive"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </Label>
                            <PhotoInput
                                id="condition"
                                value={conditionPhoto}
                                onChange={setConditionPhoto}
                                buttonLabel="Ambil Foto Kondisi"
                                hint="Ambil satu foto yang menunjukkan kondisi keseluruhan kendaraan."
                                capture
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="odometer">
                                Odometer akhir (opsional)
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
                                    km
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
                                Ada insiden selama peminjaman
                            </Label>
                        </div>
                        {incident && (
                            <div className="grid gap-2">
                                <Label htmlFor="incident_note">
                                    Keterangan insiden
                                    <span
                                        className="ml-1 text-destructive"
                                        aria-hidden="true"
                                    >
                                        *
                                    </span>
                                </Label>
                                <Textarea
                                    id="incident_note"
                                    value={incidentNote}
                                    onChange={(e) =>
                                        setIncidentNote(e.target.value)
                                    }
                                    rows={3}
                                    placeholder="Jelaskan kejadian, lokasi, dan kondisi kendaraan."
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
                            Kirim Pengembalian
                        </Button>
                        {!pickupId && (
                            <p className="text-center text-xs text-muted-foreground">
                                Pilih data pengembalian terlebih dahulu.
                            </p>
                        )}
                        {pickupId && (!handoverPhoto || !conditionPhoto) && (
                            <p className="text-center text-xs text-muted-foreground">
                                Unggah kedua foto untuk mengirim.
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
