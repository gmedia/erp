export type BadgeVariant =
    | 'default'
    | 'secondary'
    | 'destructive'
    | 'destructive-soft'
    | 'outline'
    | 'success'
    | 'warning';

// Semantik warna status: docs/ui-design-rules.md §0.1 & §2.5.
const NEUTRAL: BadgeVariant = 'outline';
const POSITIVE: BadgeVariant = 'success';
const PENDING: BadgeVariant = 'warning';
const MUTED: BadgeVariant = 'secondary';
const DANGER: BadgeVariant = 'destructive-soft';

export function statusBadgeVariant(
    status: string | null | undefined,
): BadgeVariant {
    if (!status) {
        return NEUTRAL;
    }

    const key = status.toLowerCase().replace(/\s+/g, '_');

    switch (key) {
        case 'regular':
        case 'active':
        case 'confirmed':
        case 'fully_received':
        case 'closed':
        case 'approved':
        case 'posted':
        case 'completed':
            return POSITIVE;

        case 'pending':
        case 'pending_approval':
        case 'partially_received':
        case 'open':
            return PENDING;

        case 'intern':
        case 'draft':
        case 'inactive':
            return MUTED;

        case 'rejected':
        case 'cancelled':
        case 'canceled':
        case 'failed':
        case 'overdue':
            return DANGER;

        default:
            return NEUTRAL;
    }
}

export function formatStatusLabel(status: string): string {
    return status.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
