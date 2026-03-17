import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import { PipelineAuditTrailItem } from './Columns';

interface DetailModalProps {
    item: PipelineAuditTrailItem | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function DetailModal({
    item,
    open,
    onOpenChange,
}: Readonly<DetailModalProps>) {
    if (!item) return null;

    const fromStateStyle = item.from_state_color
        ? {
              backgroundColor: item.from_state_color,
              color: '#fff',
              borderColor: item.from_state_color,
          }
        : undefined;
    const toStateStyle = item.to_state_color
        ? {
              backgroundColor: item.to_state_color,
              color: '#fff',
              borderColor: item.to_state_color,
          }
        : undefined;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] max-w-3xl flex-col">
                <DialogHeader>
                    <DialogTitle>Audit Trail Detail</DialogTitle>
                    <DialogDescription>
                        View complete transition details and metadata for this
                        audit trail record.
                    </DialogDescription>
                </DialogHeader>

                <ScrollArea className="flex-1 pr-4">
                    <div className="space-y-6">
                        {/* Status Change */}
                        <div className="flex items-center space-x-4 rounded-lg bg-muted/50 p-4">
                            <div className="flex-1 space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    From State
                                </p>
                                {item.from_state_name ? (
                                    <Badge
                                        style={fromStateStyle}
                                        variant="outline"
                                        className="px-3 py-1 text-sm"
                                    >
                                        {item.from_state_name}
                                    </Badge>
                                ) : (
                                    <Badge
                                        variant="outline"
                                        className="px-3 py-1 text-sm"
                                    >
                                        Initial
                                    </Badge>
                                )}
                            </div>

                            <div className="flex-none px-4 text-center">
                                <div className="mb-1 text-sm font-medium text-muted-foreground">
                                    {item.transition_name || 'Direct Change'}
                                </div>
                                <div className="flex items-center justify-center">
                                    <div className="relative h-[2px] w-24 bg-border">
                                        <div className="absolute top-1/2 right-0 translate-x-1/2 -translate-y-1/2 border-y-4 border-r-0 border-l-4 border-solid border-y-transparent border-l-border"></div>
                                    </div>
                                </div>
                            </div>

                            <div className="flex-1 space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    To State
                                </p>
                                {item.to_state_name ? (
                                    <Badge
                                        style={toStateStyle}
                                        variant="outline"
                                        className="px-3 py-1 text-sm"
                                    >
                                        {item.to_state_name}
                                    </Badge>
                                ) : (
                                    <span className="text-sm text-muted-foreground">
                                        -
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Basic Info */}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Entity Type
                                </p>
                                <p className="text-sm font-medium">
                                    {item.entity_type_short}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {item.entity_type}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Entity ID
                                </p>
                                <p className="text-sm font-medium">
                                    {item.entity_id}
                                </p>
                            </div>
                            {item.pipeline_name && (
                                <div className="col-span-2 space-y-1">
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Pipeline
                                    </p>
                                    <p className="text-sm font-medium">
                                        {item.pipeline_name}
                                    </p>
                                </div>
                            )}
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Performed By
                                </p>
                                <p className="text-sm font-medium">
                                    {item.performed_by_name}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Date & Time
                                </p>
                                <p className="text-sm font-medium">
                                    {formatDateTimeByRegionalSettings(
                                        item.created_at,
                                    )}
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
                            <div className="col-span-2 space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Comment
                                </p>
                                <p className="text-sm font-medium whitespace-pre-wrap">
                                    {item.comment || '-'}
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
