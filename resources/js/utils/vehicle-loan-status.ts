import { type BadgeVariant, statusBadgeVariant } from '@/lib/status-badge';

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

const STATUS_KEYS: Record<string, string> = {
    draft: 'status.draft',
    pending: 'status.pending',
    in_progress: 'status.in_progress',
    approved: 'status.approved',
    rejected: 'status.rejected',
    pickup_done: 'status.pickup_done',
    returned: 'status.returned',
    cancelled: 'status.cancelled',
};

const VARIANT_OVERRIDES: Record<string, BadgeVariant> = {
    in_progress: 'secondary',
    pickup_done: 'success',
    returned: 'success',
};

export function vehicleLoanStatusLabel(
    status: string | null | undefined,
    translate?: (key: string, fallback: string) => string,
): string {
    if (!status) {
        return '-';
    }

    const key = status.toLowerCase().replace(/\s+/g, '_');
    const fallback = LABELS[key] ?? status.replaceAll('_', ' ');

    const translationKey = STATUS_KEYS[key];

    return translate && translationKey
        ? translate(translationKey, fallback)
        : fallback;
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
