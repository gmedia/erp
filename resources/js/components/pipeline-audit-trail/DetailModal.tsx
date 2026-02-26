import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { PipelineAuditTrailItem } from './Columns';
import { ScrollArea } from '@/components/ui/scroll-area';

interface DetailModalProps {
    item: PipelineAuditTrailItem | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function DetailModal({ item, open, onOpenChange }: DetailModalProps) {
    if (!item) return null;

    const fromStateStyle = item.from_state_color ? { backgroundColor: item.from_state_color, color: '#fff', borderColor: item.from_state_color } : undefined;
    const toStateStyle = item.to_state_color ? { backgroundColor: item.to_state_color, color: '#fff', borderColor: item.to_state_color } : undefined;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-3xl max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Audit Trail Detail</DialogTitle>
                </DialogHeader>

                <ScrollArea className="flex-1 pr-4">
                    <div className="space-y-6">
                        {/* Status Change */}
                        <div className="flex items-center space-x-4 bg-muted/50 p-4 rounded-lg">
                            <div className="flex-1 space-y-1">
                                <p className="text-sm text-muted-foreground font-medium">From State</p>
                                {item.from_state_name ? (
                                    <Badge style={fromStateStyle} variant="outline" className="text-sm px-3 py-1">
                                        {item.from_state_name}
                                    </Badge>
                                ) : (
                                    <Badge variant="outline" className="text-sm px-3 py-1">Initial</Badge>
                                )}
                            </div>
                            
                            <div className="flex-none px-4 text-center">
                                <div className="text-sm font-medium text-muted-foreground mb-1">
                                    {item.transition_name || 'Direct Change'}
                                </div>
                                <div className="flex items-center justify-center">
                                    <div className="h-[2px] w-24 bg-border relative">
                                        <div className="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 border-solid border-l-border border-l-4 border-y-transparent border-y-4 border-r-0"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="flex-1 space-y-1">
                                <p className="text-sm text-muted-foreground font-medium">To State</p>
                                {item.to_state_name ? (
                                    <Badge style={toStateStyle} variant="outline" className="text-sm px-3 py-1">
                                        {item.to_state_name}
                                    </Badge>
                                ) : (
                                    <span className="text-sm text-muted-foreground">-</span>
                                )}
                            </div>
                        </div>

                        {/* Basic Info */}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground font-medium">Entity Type</p>
                                <p className="text-sm font-medium">{item.entity_type_short}</p>
                                <p className="text-xs text-muted-foreground">{item.entity_type}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground font-medium">Entity ID</p>
                                <p className="text-sm font-medium">{item.entity_id}</p>
                            </div>
                            {item.pipeline_name && (
                                <div className="space-y-1 col-span-2">
                                    <p className="text-sm text-muted-foreground font-medium">Pipeline</p>
                                    <p className="text-sm font-medium">{item.pipeline_name}</p>
                                </div>
                            )}
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground font-medium">Performed By</p>
                                <p className="text-sm font-medium">{item.performed_by_name}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground font-medium">Date & Time</p>
                                <p className="text-sm font-medium">{new Date(item.created_at).toLocaleString()}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground font-medium">IP Address</p>
                                <p className="text-sm font-medium">{item.ip_address || '-'}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground font-medium">User Agent</p>
                                <p className="text-sm font-medium truncate" title={item.user_agent || ''}>{item.user_agent || '-'}</p>
                            </div>
                            <div className="space-y-1 col-span-2">
                                <p className="text-sm text-muted-foreground font-medium">Comment</p>
                                <p className="text-sm font-medium whitespace-pre-wrap">{item.comment || '-'}</p>
                            </div>
                        </div>
                        
                        {/* Metadata JSON */}
                        {item.metadata && Object.keys(item.metadata).length > 0 && (
                            <div className="space-y-2">
                                <p className="text-sm text-muted-foreground font-medium">Metadata Snapshot</p>
                                <pre className="bg-muted p-4 rounded-lg text-xs overflow-x-auto">
                                    <code>{JSON.stringify(item.metadata, null, 2)}</code>
                                </pre>
                            </div>
                        )}
                    </div>
                </ScrollArea>
            </DialogContent>
        </Dialog>
    );
}
