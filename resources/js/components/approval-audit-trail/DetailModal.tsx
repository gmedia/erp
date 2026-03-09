import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { ApprovalAuditTrailItem } from './Columns';

interface DetailModalProps {
    item: ApprovalAuditTrailItem | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function DetailModal({ item, open, onOpenChange }: DetailModalProps) {
    if (!item) return null;

    const eventFormatted = item.event
        ? item.event.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase())
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
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] max-w-2xl flex-col">
                <DialogHeader>
                    <DialogTitle>Audit Trail Detail</DialogTitle>
                    <DialogDescription>
                        View complete details and metadata for this approval
                        action.
                    </DialogDescription>
                </DialogHeader>

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

                        {/* Basic Info */}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Document Type
                                </p>
                                <p className="text-sm font-medium">
                                    {item.approvable_type_short}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {item.approvable_type}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Document ID
                                </p>
                                <p className="text-sm font-medium">
                                    {item.approvable_id}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Actor
                                </p>
                                <p className="text-sm font-medium">
                                    {item.actor_user_name}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Date & Time
                                </p>
                                <p className="text-sm font-medium">
                                    {new Date(item.created_at).toLocaleString()}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    IP Address
                                </p>
                                <p className="text-sm font-medium">
                                    {item.ip_address || '-'}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    User Agent
                                </p>
                                <p
                                    className="truncate text-sm font-medium"
                                    title={item.user_agent || ''}
                                >
                                    {item.user_agent || '-'}
                                </p>
                            </div>
                        </div>

                        {/* Metadata JSON */}
                        {item.metadata &&
                            Object.keys(item.metadata).length > 0 && (
                                <div className="space-y-2">
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Metadata Snapshot
                                    </p>
                                    <pre className="overflow-x-auto rounded-lg bg-muted p-4 text-xs">
                                        <code>
                                            {JSON.stringify(
                                                item.metadata,
                                                null,
                                                2,
                                            )}
                                        </code>
                                    </pre>
                                </div>
                            )}
                    </div>
                </ScrollArea>
            </DialogContent>
        </Dialog>
    );
}
