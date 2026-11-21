import type { BadgeVariant } from '@/components/ui/badge';

// Peta semantik status → tone badge (docs/ui-design-rules.md §0.1 & §2.5).
// Sumber: docs/visual-audit/FINDINGS.md W4-01/W4-02.
const NEUTRAL: BadgeVariant = 'outline';
const POSITIVE: BadgeVariant = 'success';
const PENDING: BadgeVariant = 'warning';
const MUTED: BadgeVariant = 'secondary';
const DANGER: BadgeVariant = 'destructive-soft';

// Status yang selesai/diproses tanpa konotasi sukses penuh (mis. in_transit,
// approved, fully received, stocktake_result) memakai tone info.
const INFO: BadgeVariant = 'info';

export { BadgeVariant };

export function statusBadgeVariant(
    status: string | null | undefined,
): BadgeVariant {
    if (!status) {
        return NEUTRAL;
    }

    const key = status.toLowerCase().replace(/\s+/g, '_');

    switch (key) {
        // Positif: aktif, selesai, terkonfirmasi, tertutup.
        case 'active':
        case 'regular':
        case 'completed':
        case 'confirmed':
        case 'closed':
        case 'posted':
        case 'received':
        case 'fully_received':
        case 'full_paid':
        case 'paid':
        case 'returned':
        case 'reconciled':
            return POSITIVE;

        // Menunggu/terbuka: pending, draft terbuka, partially paid/received.
        case 'pending':
        case 'pending_approval':
        case 'scheduled':
        case 'reopened':
        case 'outstanding':
        case 'partially_received':
        case 'partially_paid':
        case 'partially_processed':
        case 'in_transit':
            return PENDING;

        // Sedang berjalan / disetujui (info, bukan sukses).
        case 'in_progress':
        case 'work_in_progress':
        case 'maintenance':
        case 'approved':
        case 'auto_approved':
        case 'stocktake_result':
        case 'on_hold':
        case 'sent':
            return INFO;

        // Netral-tenang: draft, tidak aktif.
        case 'draft':
        case 'intern':
        case 'inactive':
        case 'archived':
        case 'idle':
            return MUTED;

        // Negatif/danger: ditolak, dibatalkan, gagal, telat.
        case 'rejected':
        case 'step_rejected':
        case 'cancelled':
        case 'canceled':
        case 'void':
        case 'voided':
        case 'failed':
        case 'overdue':
        case 'discontinued':
        case 'expired':
        case 'locked':
        case 'disposed':
        case 'lost':
            return DANGER;

        default:
            return NEUTRAL;
    }
}

// Priority dokumen (purchase requests, dsb.): tinggi = warning, rendah = netral.
export function priorityBadgeVariant(
    priority: string | null | undefined,
): BadgeVariant {
    if (!priority) {
        return NEUTRAL;
    }

    const key = priority.toLowerCase().replace(/\s+/g, '_');

    switch (key) {
        case 'urgent':
            return DANGER;
        case 'high':
            return PENDING;
        case 'low':
            return MUTED;
        case 'normal':
        default:
            return NEUTRAL;
    }
}

// Event approval (audit trail): approved/step approved sukses,
// rejected/cancelled danger, submitted/delegated/escalated netral.
export function approvalEventBadgeVariant(
    event: string | null | undefined,
): BadgeVariant {
    if (!event) {
        return NEUTRAL;
    }

    const key = event.toLowerCase().replace(/\s+/g, '_');

    if (key.includes('rejected')) {
        return DANGER;
    }
    if (key.includes('approved')) {
        return POSITIVE;
    }
    if (key === 'cancelled' || key === 'canceled' || key === 'voided') {
        return DANGER;
    }
    if (key === 'delegated' || key === 'escalated' || key === 'submitted') {
        return MUTED;
    }

    return NEUTRAL;
}

// Adjustment type (stok): rusak/hilang = danger, expired/susut = warning,
// hasil stocktake = info, stok awal = sukses, koreksi/lainnya = netral.
export function adjustmentTypeBadgeVariant(
    type: string | null | undefined,
): BadgeVariant {
    if (!type) {
        return NEUTRAL;
    }

    const key = type.toLowerCase().replace(/\s+/g, '_');

    switch (key) {
        case 'damage':
        case 'lost':
            return DANGER;
        case 'expired':
        case 'shrinkage':
            return PENDING;
        case 'stocktake_result':
            return INFO;
        case 'initial_stock':
            return POSITIVE;
        case 'correction':
        case 'other':
        default:
            return NEUTRAL;
    }
}

// Hasil selisih stocktake aset: hilang/rusak = danger, dipindah = info.
export function stocktakeVarianceBadgeVariant(
    result: string | null | undefined,
): BadgeVariant {
    if (!result) {
        return NEUTRAL;
    }

    const key = result.toLowerCase().replace(/\s+/g, '_');

    switch (key) {
        case 'damaged':
        case 'missing':
            return DANGER;
        case 'moved':
            return INFO;
        default:
            return NEUTRAL;
    }
}

// Nama class PHP/FQCN → label manusiawi (mis. App\Models\PurchaseRequest →
// "Purchase Request", SupplierBill → "Supplier Bill") untuk kolom Entity/
// Approvable/Document Type (W4-11).
export function formatClassName(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    const short = value.split('\\').pop() ?? value;

    return short
        .replaceAll('_', ' ')
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/\s+/g, ' ')
        .trim();
}

// Humanize label status: snake_case/huruf kecil → Title Case.
// Nilai yang sudah berupa Title Case ("Bank Transfer", "Raw Material")
// tidak berubah.
export function formatStatusLabel(status: string | null | undefined): string {
    if (!status) {
        return '-';
    }

    return status.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
