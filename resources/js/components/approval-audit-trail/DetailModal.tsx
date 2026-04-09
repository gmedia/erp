import {
    AuditTrailField,
    AuditTrailMetadataSnapshot,
} from '@/components/common/AuditTrailDetail';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import { ApprovalAuditTrailItem } from './Columns';

interface DetailModalProps {
    item: ApprovalAuditTrailItem | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function DetailModal({
    item,
    open,
    onOpenChange,
}: Readonly<DetailModalProps>) {
    if (!item) return null;

    const eventFormatted = item.event
        ? item.event
              .replaceAll('_', ' ')
              .replaceAll(/\b\w/g, (letter) => letter.toUpperCase())
        : '-';
    let variant: 'default' | 'secondary' | 'destructive' | 'outline' =
        'outline';

    if (item.event.includes('approved')) variant = 'default';
    if (item.event === 'step_rejected') variant = 'destructive';
    if (item.event === 'submitted') variant = 'secondary';
    if (item.event === 'auto_approved') variant = 'default';
    if (item.event === 'delegated' || item.event === 'escalated')
        variant = 'secondary';

    return (
        <ViewModalShell
            open={open}
            onClose={() => onOpenChange(false)}
            title="Audit Trail Detail"
            description="View complete details and metadata for this approval action."
            contentClassName="flex max-h-[90vh] max-w-2xl flex-col"
            hideFooter
        >
            <ScrollArea className="flex-1 pr-4">
                <div className="space-y-6">
                    <div className="flex items-center space-x-4 rounded-lg bg-muted/50 p-4">
                        <div className="flex-1 space-y-1">
                            <p className="text-sm font-medium text-muted-foreground">
                                Event
                            </p>
                            <Badge
                                variant={variant}
                                className="px-3 py-1 text-sm"
                            >
                                {eventFormatted}
                            </Badge>
                        </div>
                        <div className="flex-1 space-y-1">
                            <p className="text-sm font-medium text-muted-foreground">
                                Request ID
                            </p>
                            <p className="text-sm font-medium">
                                {item.approval_request_id || '-'}
                            </p>
                        </div>
                        <div className="flex-1 space-y-1">
                            <p className="text-sm font-medium text-muted-foreground">
                                Step Order
                            </p>
                            <p className="text-sm font-medium">
                                {item.step_order || '-'}
                            </p>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <AuditTrailField
                            label="Document Type"
                            value={item.approvable_type_short}
                            helperText={item.approvable_type}
                        />
                        <AuditTrailField
                            label="Document ID"
                            value={item.approvable_id}
                        />
                        <AuditTrailField
                            label="Actor"
                            value={item.actor_user_name}
                        />
                        <AuditTrailField
                            label="Date & Time"
                            value={formatDateTimeByRegionalSettings(
                                item.created_at,
                            )}
                        />
                        <AuditTrailField
                            label="IP Address"
                            value={item.ip_address || '-'}
                        />
                        <AuditTrailField
                            label="User Agent"
                            value={item.user_agent || '-'}
                            valueClassName="truncate text-sm font-medium"
                        />
                    </div>

                    <AuditTrailMetadataSnapshot metadata={item.metadata} />
                </div>
            </ScrollArea>
        </ViewModalShell>
    );
}
