import { PageHeader } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { toast } from 'sonner';

export default function VehicleLoanReturnPage() {
    const [pickups, setPickups] = useState<
        Array<{ id: number; vehicle_loan_id: number }>
    >([]);
    const [pickupId, setPickupId] = useState('');
    const [handoverPhoto, setHandoverPhoto] = useState<File | null>(null);
    const [conditionPhoto, setConditionPhoto] = useState<File | null>(null);
    const [incident, setIncident] = useState(false);
    const [incidentNote, setIncidentNote] = useState('');
    const [odometerEnd, setOdometerEnd] = useState('');
    const [loading, setLoading] = useState(false);

    async function loadPickups() {
        try {
            const response = await axios.get(
                '/api/vehicle-loans/my?per_page=50',
            );
            const items = (
                response.data.data as Array<{
                    pickup?: {
                        id: number;
                        vehicle_loan_id: number;
                        key_handover_at?: string | null;
                    };
                }>
            )
                .filter((loan) => loan.pickup)
                .map((loan) => loan.pickup!)
                .filter((pickup) => Boolean(pickup.key_handover_at));
            setPickups(items);
        } catch {
            toast.error('Gagal memuat data pengambilan.');
        }
    }

    async function submit() {
        if (!pickupId || !handoverPhoto || !conditionPhoto) {
            toast.error('Lengkapi pengambilan dan kedua foto.');
            return;
        }

        const body = new FormData();
        body.append('handover_photo', handoverPhoto);
        body.append('condition_photo', conditionPhoto);
        body.append('incident', String(incident));
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
                <title>Pengembalian Kendaraan </title>
            </Helmet>
            <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
                <PageHeader
                    title="Form Pengembalian Kendaraan"
                    description="Unggah bukti serah terima, foto kondisi, dancatat insiden bila ada."
                />

                <Card>
                    <CardContent className="space-y-4 pt-4">
                        <Button
                            variant="outline"
                            onClick={loadPickups}
                            className="w-full"
                        >
                            Muat data pengambilan saya
                        </Button>

                        <div className="grid gap-2">
                            <Label htmlFor="pickup">Pengambilan</Label>
                            <Input
                                id="pickup"
                                list="my-pickups"
                                value={pickupId}
                                onChange={(e) => setPickupId(e.target.value)}
                            />
                            <datalist id="my-pickups">
                                {pickups.map((pickup) => (
                                    <option key={pickup.id} value={pickup.id}>
                                        Pengambilan #{pickup.id} — Loan #
                                        {pickup.vehicle_loan_id}
                                    </option>
                                ))}
                            </datalist>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="handover">
                                Foto bukti serah terima dengan security
                            </Label>
                            <Input
                                id="handover"
                                type="file"
                                accept="image/jpeg,image/png"
                                onChange={(e) =>
                                    setHandoverPhoto(
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="condition">
                                Foto kondisi kendaraan
                            </Label>
                            <Input
                                id="condition"
                                type="file"
                                accept="image/jpeg,image/png"
                                onChange={(e) =>
                                    setConditionPhoto(
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="odometer">
                                Odometer akhir (opsional)
                            </Label>
                            <Input
                                id="odometer"
                                inputMode="numeric"
                                value={odometerEnd}
                                onChange={(e) => setOdometerEnd(e.target.value)}
                            />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={incident}
                                onChange={(e) => setIncident(e.target.checked)}
                            />
                            Ada insiden
                        </label>
                        {incident && (
                            <div className="grid gap-2">
                                <Label htmlFor="incident_note">
                                    Keterangan insiden
                                </Label>
                                <Textarea
                                    id="incident_note"
                                    value={incidentNote}
                                    onChange={(e) =>
                                        setIncidentNote(e.target.value)
                                    }
                                    rows={3}
                                />
                            </div>
                        )}

                        <Button
                            onClick={submit}
                            disabled={loading}
                            className="w-full"
                        >
                            Kirim Pengembalian
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
