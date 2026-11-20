import { statusBadgeVariant } from '@/lib/status-badge';

type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline';

const LABELS: Record<string, string> = {
    draft: 'Draf',
    pending: 'Menunggu',
    in_progress: 'Diproses',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    pickup_done: 'Kendaraan Dipakai',
    returned: 'Selesai',
    cancelled: 'Dibatalkan',
};

const VARIANT_OVERRIDES: Record<string, BadgeVariant> = {
    in_progress: 'secondary',
    pickup_done: 'default',
    returned: 'default',
};

export function vehicleLoanStatusLabel(
    status: string | null | undefined,
): string {
    if (!status) {
        return '-';
    }

    const key = status.toLowerCase().replace(/\s+/g, '_');

    return LABELS[key] ?? status.replaceAll('_', ' ');
}

export function vehicleLoanStatusVariant(
    status: string | null | undefined,
): BadgeVariant {
    if (!status) {
        return 'outline';
    }

    const key = status.toLowerCase().replace(/\s+/g, '_');

    return VARIANT_OVERRIDES[key] ?? statusBadgeVariant(key);
}
