import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import {
    vehicleLoanStatusLabel,
    vehicleLoanStatusVariant,
} from '@/utils/vehicle-loan-status';

const STATUS_STYLES: Record<string, string> = {
    draft: 'border-slate-300 bg-slate-100 text-slate-800',
    pending: 'border-amber-300 bg-amber-100 text-amber-900',
    in_progress: 'border-amber-300 bg-amber-100 text-amber-900',
    approved: 'border-emerald-300 bg-emerald-100 text-emerald-900',
    rejected: 'border-rose-300 bg-rose-100 text-rose-900',
    pickup_done: 'border-sky-300 bg-sky-100 text-sky-900',
    returned: 'border-emerald-300 bg-emerald-100 text-emerald-900',
    cancelled: 'border-slate-300 bg-slate-100 text-slate-700',
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
