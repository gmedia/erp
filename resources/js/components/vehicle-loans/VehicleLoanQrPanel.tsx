import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';
import { QRCodeSVG } from 'qrcode.react';

interface MobileConfig {
    public_base_url: string;
    qr_paths: Record<string, string>;
}

/** Shared QR panel for every vehicle-loan form. URL comes from server config,
 * never from window.location.origin, so printed codes survive domain changes. */
export function VehicleLoanQrPanel({
    form,
}: {
    form: 'request' | 'pickup' | 'return';
}) {
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
                        aria-label="QR untuk membuka form di loket security"
                    />
                ) : (
                    <span className="px-2 text-center text-[10px] text-muted-foreground">
                        QR belum tersedia
                    </span>
                )}
            </div>
            <div className="space-y-1">
                <p className="text-sm font-medium">QR Loket Security</p>
                <p className="text-xs text-muted-foreground">
                    Cetak dan tempel QR ini di pos security. Peminjam scan,
                    login, lalu form terbuka otomatis.
                </p>
                <span className="inline-flex rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
                    Hanya untuk kiosk / cetak
                </span>
            </div>
        </div>
    );
}
