import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';
import { QRCodeSVG } from 'qrcode.react';
import { useVehicleLoanPageText } from './useVehicleLoanPageText';

interface MobileConfig {
    public_base_url: string;
    qr_paths: Record<string, string>;
}

/** Shared QR panel for every vehicle-loan form. URL comes from server config,
 * never from window.location.origin, so printed codes survive domain changes. */
export function VehicleLoanQrPanel({
    form,
}: Readonly<{
    form: 'request' | 'pickup' | 'return';
}>) {
    const { data } = useQuery({
        queryKey: ['vehicle-loans', 'mobile-config'],
        queryFn: async () => {
            const response = await axios.get<MobileConfig>(
                '/api/vehicle-loans/mobile-config',
            );
            return response.data;
        },
        staleTime: 60 * 60 * 1000,
    });

    const tPage = useVehicleLoanPageText();
    const base = (data?.public_base_url ?? '').replace(/\/$/, '');
    const path = data?.qr_paths?.[form] ?? `/vehicle-loans/${form}`;
    const url = `${base}${path}`;

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div className="flex h-24 w-24 shrink-0 items-center justify-center rounded-md border bg-white p-1">
                {url ? (
                    <QRCodeSVG
                        value={url}
                        size={88}
                        aria-label={tPage(
                            'qr_aria_label',
                            'QR untuk membuka form di loket keamanan',
                        )}
                    />
                ) : (
                    <span className="px-2 text-center text-[10px] text-muted-foreground">
                        {tPage('qr_unavailable', 'QR belum tersedia')}
                    </span>
                )}
            </div>
            <div className="space-y-1">
                <p className="text-sm font-medium">
                    {tPage('qr_title', 'QR Pos Keamanan')}
                </p>
                <p className="text-xs text-muted-foreground">
                    {tPage(
                        'qr_description',
                        'Cetak dan tempel QR ini di pos keamanan. Peminjam pindai, masuk, lalu formulir terbuka otomatis.',
                    )}
                </p>
                <span className="inline-flex rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
                    {tPage('qr_kiosk_badge', 'Hanya untuk kios / cetak')}
                </span>
            </div>
        </div>
    );
}
