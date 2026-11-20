import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import {
    vehicleLoanStatusLabel,
    vehicleLoanStatusVariant,
} from '@/utils/vehicle-loan-status';

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
};

export function VehicleLoanStatusBadge({
    status,
    className,
}: {
    status: string | null | undefined;
    className?: string;
}) {
    const key = (status ?? '').toLowerCase().replace(/\s+/g, '_');

    return (
        <Badge
            variant={vehicleLoanStatusVariant(status)}
            className={cn('border', STATUS_STYLES[key], className)}
        >
            {vehicleLoanStatusLabel(status)}
        </Badge>
    );
}
