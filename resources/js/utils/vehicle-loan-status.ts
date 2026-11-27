import { type BadgeVariant, statusBadgeVariant } from '@/lib/status-badge';

/**
 * Kunci status turunan (bukan kolom DB): pengambilan sudah dikirim tetapi kunci
 * belum diserahkan. Dipakai bersama oleh badge detail, antrean keamanan, dan dasbor.
 */
export const AWAITING_KEY_HANDOVER = 'awaiting_key_handover';

const LABELS: Record<string, string> = {
    draft: 'Draf',
    pending: 'Menunggu',
    in_progress: 'Diproses',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    pickup_done: 'Kendaraan Dipakai',
    returned: 'Selesai',
    cancelled: 'Dibatalkan',
    [AWAITING_KEY_HANDOVER]: 'Menunggu Serah Kunci',
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
    [AWAITING_KEY_HANDOVER]: 'status.awaiting_key_handover',
};

const VARIANT_OVERRIDES: Record<string, BadgeVariant> = {
    in_progress: 'secondary',
    pickup_done: 'success',
    returned: 'success',
    [AWAITING_KEY_HANDOVER]: 'warning',
};

type LoanStatusInput = {
    status: string | null | undefined;
    pickup?: { key_handover_at?: string | null } | null;
};

export function vehicleLoanDisplayStatus(
    loan: LoanStatusInput | null | undefined,
): string | null | undefined {
    if (!loan) {
        return null;
    }

    if (loan.status === 'pickup_done' && !loan.pickup?.key_handover_at) {
        return AWAITING_KEY_HANDOVER;
    }

    return loan.status;
}

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

export function approvalStepStatusLabel(
    status: string | null | undefined,
    translate?: (key: string, fallback: string) => string,
): string {
    return vehicleLoanStatusLabel(status, translate);
}
