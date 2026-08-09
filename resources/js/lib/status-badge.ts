type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline';

const NEUTRAL: BadgeVariant = 'outline';
const POSITIVE: BadgeVariant = 'default';
const MUTED: BadgeVariant = 'secondary';
const DANGER: BadgeVariant = 'destructive';

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

        case 'intern':
        case 'draft':
        case 'pending':
        case 'pending_approval':
        case 'partially_received':
        case 'open':
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
