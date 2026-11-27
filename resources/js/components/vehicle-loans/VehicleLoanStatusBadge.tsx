import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import {
    AWAITING_KEY_HANDOVER,
    vehicleLoanDisplayStatus,
    vehicleLoanStatusLabel,
    vehicleLoanStatusVariant,
} from '@/utils/vehicle-loan-status';

import { useVehicleLoanPageText } from './useVehicleLoanPageText';

const STATUS_STYLES: Record<string, string> = {
    draft: 'border-border bg-muted text-foreground',
    pending: 'border-warning/30 bg-warning-soft text-warning',
    in_progress: 'border-warning/30 bg-warning-soft text-warning',
    approved: 'border-success/30 bg-success-soft text-success',
    rejected:
        'border-destructive/30 bg-destructive-soft text-destructive-foreground',
    pickup_done: 'border-info/30 bg-info/10 text-info',
    returned: 'border-success/30 bg-success-soft text-success',
    cancelled: 'border-border bg-muted text-foreground',
    [AWAITING_KEY_HANDOVER]: 'border-warning/30 bg-warning-soft text-warning',
};

export function VehicleLoanStatusBadge({
    status,
    loan,
    className,
}: Readonly<{
    status?: string | null;
    loan?: {
        status: string | null | undefined;
        pickup?: { key_handover_at?: string | null } | null;
    } | null;
    className?: string;
}>) {
    const tPage = useVehicleLoanPageText();
    const displayStatus = loan ? vehicleLoanDisplayStatus(loan) : status;
    const key = (displayStatus ?? '').toLowerCase().replace(/\s+/g, '_');

    return (
        <Badge
            variant={vehicleLoanStatusVariant(displayStatus)}
            className={cn('border', STATUS_STYLES[key], className)}
        >
            {vehicleLoanStatusLabel(displayStatus, tPage)}
        </Badge>
    );
}
