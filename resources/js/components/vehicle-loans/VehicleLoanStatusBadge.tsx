import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import {
    vehicleLoanStatusLabel,
    vehicleLoanStatusVariant,
} from '@/utils/vehicle-loan-status';

const STATUS_STYLES: Record<string, string> = {
    draft: 'border-slate-200 bg-slate-100 text-slate-700',
    pending: 'border-amber-200 bg-amber-50 text-amber-800',
    in_progress: 'border-amber-200 bg-amber-50 text-amber-800',
    approved: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    rejected: 'border-rose-200 bg-rose-50 text-rose-800',
    pickup_done: 'border-sky-200 bg-sky-50 text-sky-800',
    returned: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    cancelled: 'border-slate-200 bg-slate-50 text-slate-600',
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
