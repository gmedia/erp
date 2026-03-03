import { useEffect } from 'react';
import { useEntityApprovalHistory } from '@/hooks/useEntityApprovalHistory';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { ScrollArea } from '@/components/ui/scroll-area';
import { format } from 'date-fns';
import { CheckCircle2, XCircle, Clock, SkipForward, Send, AlertCircle, RefreshCw } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

interface Props {
    entityType: string;
    entityId: string | number;
}

export function ApprovalHistoryTimeline({ entityType, entityId }: Props) {
    const { history, loading, fetchHistory } = useEntityApprovalHistory({ entityType, entityId });

    useEffect(() => {
        fetchHistory();
    }, [fetchHistory]);

    if (loading && history.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <Skeleton className="h-6 w-48" />
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

    if (history.length === 0) {
        return (
            <Card>
                <CardHeader className="pb-3 border-b border-border/50">
                    <CardTitle className="text-lg">Approval History</CardTitle>
                    <CardDescription>No approval history available for this document.</CardDescription>
                </CardHeader>
            </Card>
        );
    }

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'approved': return <CheckCircle2 className="w-4 h-4 text-emerald-500" />;
            case 'rejected': return <XCircle className="w-4 h-4 text-rose-500" />;
            case 'pending': return <Clock className="w-4 h-4 text-amber-500" />;
            case 'skipped': return <SkipForward className="w-4 h-4 text-slate-500" />;
            case 'cancelled': return <AlertCircle className="w-4 h-4 text-slate-500" />;
            default: return <Clock className="w-4 h-4" />;
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'approved': return <Badge variant="outline" className="bg-emerald-500/10 text-emerald-500 border-emerald-500/20">Approved</Badge>;
            case 'rejected': return <Badge variant="outline" className="bg-rose-500/10 text-rose-500 border-rose-500/20">Rejected</Badge>;
            case 'pending': return <Badge variant="outline" className="bg-amber-500/10 text-amber-500 border-amber-500/20">Pending</Badge>;
            case 'skipped': return <Badge variant="outline" className="bg-slate-500/10 text-slate-500 border-slate-500/20">Skipped</Badge>;
            case 'cancelled': return <Badge variant="outline" className="bg-slate-500/10 text-slate-500 border-slate-500/20">Cancelled</Badge>;
            case 'in_progress': return <Badge variant="outline" className="bg-blue-500/10 text-blue-500 border-blue-500/20">In Progress</Badge>;
            default: return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <Card>
            <CardHeader className="pb-3 border-b border-border/50 flex flex-row items-center justify-between">
                <div>
                    <CardTitle className="text-lg">Approval History</CardTitle>
                    <CardDescription>Timeline of approval requests and decisions</CardDescription>
                </div>
                <Badge variant="secondary" className="font-normal cursor-pointer" onClick={fetchHistory}>
                    <RefreshCw className="mr-1 h-3 w-3" /> Refresh
                </Badge>
            </CardHeader>
            <CardContent className="p-0">
                <ScrollArea className="h-[500px] p-6 lg:p-8">
                    <div className="space-y-10">
                        {history.map((request, reqIndex) => (
                            <div key={request.id} className="relative">
                                {/* Request Header */}
                                <div className="flex items-center justify-between mb-6 bg-muted/30 p-4 rounded-lg border border-border/50">
                                    <div className="flex flex-col gap-1">
                                        <div className="flex items-center gap-2">
                                            <Send className="w-4 h-4 text-muted-foreground" />
                                            <span className="font-semibold text-sm">
                                                Approval Request #{request.id}
                                            </span>
                                            {reqIndex === 0 && <Badge variant="secondary" className="ml-2">Latest</Badge>}
                                            {getStatusBadge(request.status)}
                                        </div>
                                        <div className="text-xs text-muted-foreground mt-1">
                                            Submitted by <span className="font-medium text-foreground">{request.submitter?.name}</span> on {format(new Date(request.submitted_at), 'dd MMM yyyy, HH:mm')}
                                        </div>
                                    </div>
                                    {request.completed_at && (
                                        <div className="text-xs text-muted-foreground text-right border-l pl-4 border-border/50">
                                            Completed:<br />
                                            {format(new Date(request.completed_at), 'dd MMM yyyy, HH:mm')}
                                        </div>
                                    )}
                                </div>

                                {/* Request Steps Timeline */}
                                <div className="relative border-l-2 border-muted pl-6 ml-6 space-y-8">
                                    {request.steps && request.steps.length > 0 ? (
                                        request.steps.map((step) => (
                                            <div key={step.id} className="relative">
                                                {/* Timeline Node */}
                                                <span className="absolute -left-[35px] flex h-8 w-8 items-center justify-center rounded-full border-4 border-background bg-background shadow-sm">
                                                    {getStatusIcon(step.status)}
                                                </span>

                                                <div className="flex flex-col gap-1.5">
                                                    <div className="flex items-center justify-between gap-4">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <span className="font-semibold text-sm">
                                                                Step {step.step_order}: {step.flow_step?.name || `Approval Level ${step.step_order}`}
                                                            </span>
                                                            <span className="text-muted-foreground text-xs mx-1">&bull;</span>
                                                            {getStatusBadge(step.status)}
                                                        </div>
                                                        {step.acted_at ? (
                                                            <time className="text-xs text-muted-foreground shrink-0" dateTime={step.acted_at}>
                                                                {format(new Date(step.acted_at), 'dd MMM yyyy, HH:mm')}
                                                            </time>
                                                        ) : (
                                                            <span className="text-xs text-muted-foreground italic">No action yet</span>
                                                        )}
                                                    </div>

                                                    <div className="text-sm text-foreground/80">
                                                        {step.status === 'pending' ? (
                                                            <span className="italic">Waiting for approval...</span>
                                                        ) : (
                                                            <>
                                                                Action by <span className="font-medium text-foreground">{step.acted_by?.name || 'System'}</span>
                                                                {step.delegated_from && (
                                                                    <span className="text-muted-foreground"> (delegated from {step.delegated_from.name})</span>
                                                                )}
                                                            </>
                                                        )}
                                                    </div>

                                                    {step.comments && (
                                                        <div className="mt-2 flex items-start gap-2 text-sm text-muted-foreground bg-muted/20 p-3 rounded-md border border-border/30">
                                                            <span className="italic">"{step.comments}"</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="text-sm text-muted-foreground italic py-2">
                                            No steps configured for this request.
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </ScrollArea>
            </CardContent>
        </Card>
    );
}
