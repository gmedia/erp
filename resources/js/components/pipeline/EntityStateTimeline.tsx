import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Skeleton } from '@/components/ui/skeleton';
import { useEntityPipeline } from '@/hooks/useEntityPipeline';
import { format } from 'date-fns';
import * as LucideIcons from 'lucide-react';
import { useEffect } from 'react';

interface Props {
    entityType: string;
    entityId: string | number;
}

export function EntityStateTimeline({ entityType, entityId }: Props) {
    const { timeline, timelineLoading, fetchTimeline } = useEntityPipeline(
        entityType,
        entityId,
    );

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
                    {[1, 2, 3].map((i) => (
                        <div key={i} className="flex gap-4">
                            <Skeleton className="h-10 w-10 rounded-full" />
                            <div className="flex-1 space-y-2">
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
            <CardHeader className="border-b border-border/50 pb-3">
                <CardTitle className="text-lg">State History</CardTitle>
                <CardDescription>
                    Timeline of status changes and actions taken
                </CardDescription>
            </CardHeader>
            <CardContent className="p-0">
                <ScrollArea className="h-[400px] p-6 lg:p-8">
                    <div className="relative ml-3 space-y-8 border-l-2 border-muted pl-6">
                        {timeline.map((entry, idx) => {
                            const isInitial = idx === timeline.length - 1;
                            const IconComponent = entry.to_state?.icon
                                ? (LucideIcons as any)[entry.to_state.icon]
                                : LucideIcons.Activity;

                            return (
                                <div key={entry.id} className="relative">
                                    {/* Timeline Node / Circle */}
                                    <span
                                        className="absolute -left-[35px] flex h-8 w-8 items-center justify-center rounded-full border-4 border-background shadow-sm"
                                        style={{
                                            backgroundColor:
                                                entry.to_state?.color ||
                                                '#cbd5e1',
                                        }}
                                    >
                                        <IconComponent className="h-3.5 w-3.5 text-white" />
                                    </span>

                                    <div className="flex flex-col gap-1.5">
                                        <div className="flex items-center justify-between gap-4">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="text-sm font-semibold">
                                                    {isInitial
                                                        ? 'Assigned Initial State'
                                                        : entry.transition
                                                              ?.name ||
                                                          'Status Changed'}
                                                </span>
                                                <span className="mx-1 text-xs text-muted-foreground">
                                                    &bull;
                                                </span>
                                                <span
                                                    className="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium"
                                                    style={{
                                                        backgroundColor: `${entry.to_state?.color}15`,
                                                        color:
                                                            entry.to_state
                                                                ?.color ||
                                                            '#475569',
                                                    }}
                                                >
                                                    {entry.to_state?.name ||
                                                        'Unknown'}
                                                </span>
                                            </div>
                                            <time
                                                className="shrink-0 text-xs text-muted-foreground"
                                                dateTime={entry.created_at}
                                            >
                                                {format(
                                                    new Date(entry.created_at),
                                                    'dd MMM yyyy, HH:mm',
                                                )}
                                            </time>
                                        </div>

                                        <div className="text-sm text-foreground/80">
                                            Performed by{' '}
                                            <span className="font-medium text-foreground">
                                                {entry.performed_by?.name ||
                                                    'System'}
                                            </span>
                                        </div>

                                        {entry.comment && (
                                            <div className="mt-1 flex items-start gap-2 rounded-md border border-border/50 bg-muted/30 p-3 text-sm text-muted-foreground">
                                                <LucideIcons.MessageCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                                <span className="italic">
                                                    "{entry.comment}"
                                                </span>
                                            </div>
                                        )}

                                        {!isInitial &&
                                            entry.from_state &&
                                            idx !== 0 && (
                                                <div className="mt-1opacity-70 mt-2 flex items-center gap-1.5 text-xs text-muted-foreground">
                                                    <span>Previous state:</span>
                                                    <span className="font-medium">
                                                        {entry.from_state.name}
                                                    </span>
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
