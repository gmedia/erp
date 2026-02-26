import { useEffect } from 'react';
import { useEntityPipeline } from '@/hooks/useEntityPipeline';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { ScrollArea } from '@/components/ui/scroll-area';
import { format } from 'date-fns';
import * as LucideIcons from 'lucide-react';

interface Props {
    entityType: string;
    entityId: string | number;
}

export function EntityStateTimeline({ entityType, entityId }: Props) {
    const { 
        timeline, 
        timelineLoading, 
        fetchTimeline 
    } = useEntityPipeline(entityType, entityId);

    useEffect(() => {
        fetchTimeline();
    }, [fetchTimeline]);

    if (timelineLoading && timeline.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <Skeleton className="h-6 w-32" />
                    <Skeleton className="h-4 w-64" />
                </CardHeader>
                <CardContent className="space-y-4">
                    {[1, 2, 3].map(i => (
                        <div key={i} className="flex gap-4">
                            <Skeleton className="h-10 w-10 rounded-full" />
                            <div className="space-y-2 flex-1">
                                <Skeleton className="h-4 w-full" />
                                <Skeleton className="h-4 w-3/4" />
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>
        );
    }

    if (timeline.length === 0) {
        return null; // Don't show if no history (or no pipeline)
    }

    return (
        <Card>
            <CardHeader className="pb-3 border-b border-border/50">
                <CardTitle className="text-lg">State History</CardTitle>
                <CardDescription>Timeline of status changes and actions taken</CardDescription>
            </CardHeader>
            <CardContent className="p-0">
                <ScrollArea className="h-[400px] p-6 lg:p-8">
                    <div className="relative border-l-2 border-muted pl-6 ml-3 space-y-8">
                        {timeline.map((entry, idx) => {
                            const isInitial = idx === timeline.length - 1;
                            const IconComponent = entry.to_state?.icon ? (LucideIcons as any)[entry.to_state.icon] : LucideIcons.Activity;

                            return (
                                <div key={entry.id} className="relative">
                                    {/* Timeline Node / Circle */}
                                    <span 
                                        className="absolute -left-[35px] flex h-8 w-8 items-center justify-center rounded-full border-4 border-background shadow-sm"
                                        style={{ backgroundColor: entry.to_state?.color || '#cbd5e1' }}
                                    >
                                        <IconComponent className="h-3.5 w-3.5 text-white" />
                                    </span>

                                    <div className="flex flex-col gap-1.5">
                                        <div className="flex items-center justify-between gap-4">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="font-semibold text-sm">
                                                    {isInitial ? 'Assigned Initial State' : entry.transition?.name || 'Status Changed'}
                                                </span>
                                                <span className="text-muted-foreground text-xs mx-1">&bull;</span>
                                                <span 
                                                    className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                    style={{ 
                                                        backgroundColor: `${entry.to_state?.color}15`, 
                                                        color: entry.to_state?.color || '#475569'
                                                    }}
                                                >
                                                    {entry.to_state?.name || 'Unknown'}
                                                </span>
                                            </div>
                                            <time className="text-xs text-muted-foreground shrink-0" dateTime={entry.created_at}>
                                                {format(new Date(entry.created_at), 'dd MMM yyyy, HH:mm')}
                                            </time>
                                        </div>

                                        <div className="text-sm text-foreground/80">
                                            Performed by <span className="font-medium text-foreground">{entry.performed_by?.name || 'System'}</span>
                                        </div>

                                        {entry.comment && (
                                            <div className="mt-1 flex items-start gap-2 text-sm text-muted-foreground bg-muted/30 p-3 rounded-md border border-border/50">
                                                <LucideIcons.MessageCircle className="w-4 h-4 mt-0.5 shrink-0" />
                                                <span className="italic">"{entry.comment}"</span>
                                            </div>
                                        )}

                                        {!isInitial && entry.from_state && idx !== 0 && (
                                            <div className="flex items-center gap-1.5 text-xs text-muted-foreground mt-1opacity-70 mt-2">
                                                <span>Previous state:</span>
                                                <span className="font-medium">{entry.from_state.name}</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </ScrollArea>
            </CardContent>
        </Card>
    );
}
