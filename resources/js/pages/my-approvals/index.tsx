import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { ApprovalRequest, ApprovalRequestStep } from '@/types/approval';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { formatDistanceToNow } from 'date-fns';
import { Check, Clock, Eye, Loader2, X } from 'lucide-react';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';

interface MyApprovalsResponse {
    pending: ApprovalRequestStep[];
    approved: ApprovalRequestStep[];
    rejected: ApprovalRequestStep[];
    all: ApprovalRequestStep[];
}

export default function MyApprovalsPage() {
    const queryClient = useQueryClient();
    const [actionDialog, setActionDialog] = useState<{
        open: boolean;
        type: 'approve' | 'reject' | null;
        requestStep: ApprovalRequestStep | null;
    }>({
        open: false,
        type: null,
        requestStep: null,
    });
    const [comments, setComments] = useState('');
    const [processing, setProcessing] = useState(false);

    const { data, isLoading, error } = useQuery<MyApprovalsResponse>({
        queryKey: ['my-approvals'],
        queryFn: async () => {
            const response = await axios.get('/api/my-approvals');
            return response.data;
        },
    });

    const pending = data?.pending ?? [];
    const approved = data?.approved ?? [];
    const rejected = data?.rejected ?? [];
    const all = data?.all ?? [];

    const openActionDialog = (
        type: 'approve' | 'reject',
        step: ApprovalRequestStep,
    ) => {
        setActionDialog({ open: true, type, requestStep: step });
        setComments('');
    };

    const handleAction = () => {
        if (!actionDialog.requestStep || !actionDialog.type) return;

        setProcessing(true);
        const url = `/api/my-approvals/${actionDialog.requestStep.request.id}/${actionDialog.type}`;

        axios
            .post(url, { comments })
            .then(() => {
                setActionDialog({ open: false, type: null, requestStep: null });
                queryClient.invalidateQueries({ queryKey: ['my-approvals'] });
                toast.success(`Request ${actionDialog.type}d successfully`);
            })
            .catch((error) => {
                toast.error(`Failed to ${actionDialog.type} request`);
                console.error(error);
            })
            .finally(() => {
                setProcessing(false);
            });
    };

    const getDocUrl = (request: ApprovalRequest) => {
        const type = request.approvable_type.split('\\').pop();
        const id = request.approvable_id;
        const ulid = request.approvable?.ulid;

        switch (type) {
            case 'Asset':
                return `/assets/${ulid || id}`;
            default:
                return '#';
        }
    };

    const StatusBadge = ({ status }: { status: string }) => {
        switch (status) {
            case 'pending':
                return (
                    <Badge variant="secondary">
                        <Clock className="mr-1 h-3 w-3" /> Pending
                    </Badge>
                );
            case 'approved':
                return (
                    <Badge
                        variant="default"
                        className="bg-green-600 hover:bg-green-700"
                    >
                        <Check className="mr-1 h-3 w-3" /> Approved
                    </Badge>
                );
            case 'rejected':
                return (
                    <Badge variant="destructive">
                        <X className="mr-1 h-3 w-3" /> Rejected
                    </Badge>
                );
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    const renderList = (
        items: ApprovalRequestStep[],
        isPendingTab: boolean = false,
    ) => {
        if (!items || items.length === 0) {
            return (
                <div className="mt-4 flex flex-col items-center justify-center rounded-lg border border-dashed bg-muted/20 p-8 text-center">
                    <Clock className="mb-3 h-10 w-10 text-muted-foreground/50" />
                    <h3 className="text-lg font-medium">No requests found</h3>
                    <p className="mt-1 text-sm text-muted-foreground">
                        You're all caught up!
                    </p>
                </div>
            );
        }

        return (
            <div className="mt-4 space-y-4">
                {items.map((step: ApprovalRequestStep) => (
                    <Card
                        key={step.id}
                        className="transition-all hover:shadow-md"
                    >
                        <CardContent className="p-4 sm:p-6">
                            <div className="flex flex-col gap-4">
                                <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                variant="outline"
                                                className="text-[10px] tracking-wider text-muted-foreground uppercase"
                                            >
                                                {step.request.approvable_type
                                                    .split('\\')
                                                    .pop()}
                                            </Badge>
                                            <span className="text-sm font-medium text-muted-foreground">
                                                Ref #
                                                {step.request.approvable_id}
                                            </span>
                                            <StatusBadge status={step.status} />
                                        </div>
                                        <h4 className="text-md mt-2 font-semibold">
                                            {step.flowStep?.name ||
                                                'Approval Step'}
                                        </h4>
                                        {step.request.approvable && (
                                            <div className="text-sm font-medium">
                                                {step.request.approvable
                                                    .asset_code && (
                                                    <span className="mr-2 rounded bg-muted px-1 font-mono text-xs">
                                                        {
                                                            step.request
                                                                .approvable
                                                                .asset_code
                                                        }
                                                    </span>
                                                )}
                                                {step.request.approvable.name ||
                                                    step.request.approvable
                                                        .description}
                                            </div>
                                        )}
                                        <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                            <span>
                                                Submitted by{' '}
                                                {step.request.submitter?.name ||
                                                    'Unknown'}
                                            </span>
                                            <span>•</span>
                                            <span>
                                                {step.request.submitted_at
                                                    ? formatDistanceToNow(
                                                          new Date(
                                                              step.request.submitted_at,
                                                          ),
                                                          { addSuffix: true },
                                                      )
                                                    : ''}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="w-full sm:w-auto sm:shrink-0">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            className="w-full sm:w-auto"
                                            asChild
                                        >
                                            <Link to={getDocUrl(step.request)}>
                                                <Eye className="mr-2 h-4 w-4" />{' '}
                                                View Doc
                                            </Link>
                                        </Button>
                                    </div>
                                </div>

                                {isPendingTab && (
                                    <div className="flex w-full flex-wrap items-center gap-2 border-t pt-3">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            className="w-full border-green-200 bg-green-50 text-green-700 hover:bg-green-100 hover:text-green-800 sm:w-auto"
                                            onClick={() =>
                                                openActionDialog(
                                                    'approve',
                                                    step,
                                                )
                                            }
                                        >
                                            <Check className="mr-1 h-4 w-4" />{' '}
                                            Approve
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            className="w-full border-red-200 bg-red-50 text-red-700 hover:bg-red-100 hover:text-red-800 sm:w-auto"
                                            onClick={() =>
                                                openActionDialog('reject', step)
                                            }
                                        >
                                            <X className="mr-1 h-4 w-4" />{' '}
                                            Reject
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Home', href: '/' },
                { title: 'My Approvals', href: '/my-approvals' },
            ]}
        >
            <Helmet>
                <title>
                    My Approvals - {import.meta.env.VITE_APP_NAME || 'ERP'}
                </title>
            </Helmet>

            <div className="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Approval Inbox
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        Manage all documents and requests requiring your
                        attention.
                    </p>
                </div>

                {isLoading && (
                    <div className="flex items-center gap-2 rounded-lg border bg-card p-4 text-sm text-muted-foreground">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        Loading approvals...
                    </div>
                )}

                {error && (
                    <div className="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
                        Failed to load approvals. Please refresh and try again.
                    </div>
                )}

                <Tabs defaultValue="pending" className="w-full">
                    <TabsList className="grid w-full grid-cols-4 lg:w-[500px]">
                        <TabsTrigger value="pending">Pending</TabsTrigger>
                        <TabsTrigger value="approved">Approved</TabsTrigger>
                        <TabsTrigger value="rejected">Rejected</TabsTrigger>
                        <TabsTrigger value="all">All</TabsTrigger>
                    </TabsList>

                    <TabsContent value="pending" className="mt-6">
                        {renderList(pending, true)}
                    </TabsContent>
                    <TabsContent value="approved" className="mt-6">
                        {renderList(approved, false)}
                    </TabsContent>
                    <TabsContent value="rejected" className="mt-6">
                        {renderList(rejected, false)}
                    </TabsContent>
                    <TabsContent value="all" className="mt-6">
                        {renderList(all, false)}
                    </TabsContent>
                </Tabs>
            </div>

            <Dialog
                open={actionDialog.open}
                onOpenChange={(open) =>
                    !open && setActionDialog({ ...actionDialog, open: false })
                }
            >
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>
                            {actionDialog.type === 'approve'
                                ? 'Approve Request'
                                : 'Reject Request'}
                        </DialogTitle>
                        <DialogDescription>
                            {actionDialog.type === 'approve'
                                ? 'Are you sure you want to approve this request? A comment is optional.'
                                : 'Please provide a reason for rejecting this request.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="comments">
                                Comments{' '}
                                {actionDialog.type === 'reject' && (
                                    <span className="text-destructive">*</span>
                                )}
                            </Label>
                            <Textarea
                                id="comments"
                                placeholder={
                                    actionDialog.type === 'approve'
                                        ? 'Optional notes...'
                                        : 'Reason for rejection...'
                                }
                                value={comments}
                                onChange={(e) => setComments(e.target.value)}
                                className="col-span-3"
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() =>
                                setActionDialog({
                                    ...actionDialog,
                                    open: false,
                                })
                            }
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant={
                                actionDialog.type === 'approve'
                                    ? 'default'
                                    : 'destructive'
                            }
                            onClick={handleAction}
                            disabled={
                                processing ||
                                (actionDialog.type === 'reject' &&
                                    comments.trim() === '')
                            }
                        >
                            {processing
                                ? 'Processing...'
                                : actionDialog.type === 'approve'
                                  ? 'Confirm Approval'
                                  : 'Confirm Rejection'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
