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
        <div className="flex items-center gap-4">
            <QRCodeSVG value={url} size={96} />
            <p className="text-xs text-muted-foreground">
                Scan QR ini di loket security, lalu login untuk membuka form.
                <span className="mt-1 block font-mono text-[10px] break-all">
                    {url}
                </span>
            </p>
        </div>
    );
}
