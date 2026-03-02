import { useState } from 'react';
import { Head, usePage, router, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDistanceToNow } from 'date-fns';
import { Clock, Check, X, Eye } from 'lucide-react';

export default function MyApprovalsPage({ pending, approved, rejected, all }: any) {
    const [actionDialog, setActionDialog] = useState<{ open: boolean, type: 'approve' | 'reject' | null, requestStep: any }>({
        open: false,
        type: null,
        requestStep: null,
    });
    const [comments, setComments] = useState('');
    const [processing, setProcessing] = useState(false);

    const openActionDialog = (type: 'approve' | 'reject', step: any) => {
        setActionDialog({ open: true, type, requestStep: step });
        setComments('');
    };

    const handleAction = () => {
        if (!actionDialog.requestStep || !actionDialog.type) return;

        setProcessing(true);
        const url = `/my-approvals/${actionDialog.requestStep.request.id}/${actionDialog.type}`;

        router.post(url, { comments }, {
            onSuccess: () => {
                setActionDialog({ open: false, type: null, requestStep: null });
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            },
            onFinish: () => {
                setProcessing(false);
            }
        });
    };

    const getDocUrl = (request: any) => {
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
                return <Badge variant="secondary"><Clock className="w-3 h-3 mr-1" /> Pending</Badge>;
            case 'approved':
                return <Badge variant="default" className="bg-green-600 hover:bg-green-700"><Check className="w-3 h-3 mr-1" /> Approved</Badge>;
            case 'rejected':
                return <Badge variant="destructive"><X className="w-3 h-3 mr-1" /> Rejected</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    const renderList = (items: any[], isPendingTab: boolean = false) => {
        if (!items || items.length === 0) {
            return (
                <div className="flex flex-col items-center justify-center p-8 text-center border rounded-lg border-dashed bg-muted/20 mt-4">
                    <Clock className="h-10 w-10 text-muted-foreground/50 mb-3" />
                    <h3 className="text-lg font-medium">No requests found</h3>
                    <p className="text-sm text-muted-foreground mt-1">You're all caught up!</p>
                </div>
            );
        }

        return (
            <div className="space-y-4 mt-4">
                {items.map((step: any) => (
                    <Card key={step.id} className="transition-all hover:shadow-md">
                        <CardContent className="p-4 sm:p-6">
                            <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                                <div className="space-y-1 flex-1">
                                    <div className="flex items-center gap-2">
                                        <Badge variant="outline" className="uppercase text-[10px] tracking-wider text-muted-foreground">
                                            {step.request.approvable_type.split('\\').pop()}
                                        </Badge>
                                        <span className="text-sm font-medium text-muted-foreground">
                                            Ref #{step.request.approvable_id}
                                        </span>
                                        <StatusBadge status={step.status} />
                                    </div>
                                    <h4 className="text-md font-semibold mt-2">
                                        {step.flowStep?.name || 'Approval Step'}
                                    </h4>
                                    {step.request.approvable && (
                                        <div className="text-sm font-medium">
                                            {step.request.approvable.asset_code && <span className="mr-2 font-mono text-xs bg-muted px-1 rounded">{step.request.approvable.asset_code}</span>}
                                            {step.request.approvable.name || step.request.approvable.description}
                                        </div>
                                    )}
                                    <div className="text-sm text-muted-foreground flex items-center gap-2 flex-wrap">
                                        <span>Submitted by {step.request.submitter?.name || 'Unknown'}</span>
                                        <span>•</span>
                                        <span>{step.request.submitted_at ? formatDistanceToNow(new Date(step.request.submitted_at), { addSuffix: true }) : ''}</span>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2 mt-4 sm:mt-0 w-full sm:w-auto">
                                    {isPendingTab && (
                                        <>
                                            <Button 
                                                size="sm" 
                                                variant="outline" 
                                                className="border-green-200 bg-green-50 text-green-700 hover:bg-green-100 hover:text-green-800 flex-1 sm:flex-none"
                                                onClick={() => openActionDialog('approve', step)}
                                            >
                                                <Check className="h-4 w-4 mr-1" /> Approve
                                            </Button>
                                            <Button 
                                                size="sm" 
                                                variant="outline" 
                                                className="border-red-200 bg-red-50 text-red-700 hover:bg-red-100 hover:text-red-800 flex-1 sm:flex-none"
                                                onClick={() => openActionDialog('reject', step)}
                                            >
                                                <X className="h-4 w-4 mr-1" /> Reject
                                            </Button>
                                        </>
                                    )}
                                    <Button size="sm" variant="ghost" asChild>
                                        <Link href={getDocUrl(step.request)}>
                                            <Eye className="h-4 w-4 mr-2" /> View Doc
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Home', href: '/' }, { title: 'My Approvals', href: '/my-approvals' }]}>
            <Head title="My Approvals" />
            
            <div className="p-4 sm:p-6 max-w-6xl mx-auto space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Approval Inbox</h1>
                    <p className="text-muted-foreground mt-2">Manage all documents and requests requiring your attention.</p>
                </div>

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

            <Dialog open={actionDialog.open} onOpenChange={(open) => !open && setActionDialog({ ...actionDialog, open: false })}>
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>
                            {actionDialog.type === 'approve' ? 'Approve Request' : 'Reject Request'}
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
                                Comments {actionDialog.type === 'reject' && <span className="text-destructive">*</span>}
                            </Label>
                            <Textarea 
                                id="comments" 
                                placeholder={actionDialog.type === 'approve' ? "Optional notes..." : "Reason for rejection..."} 
                                value={comments}
                                onChange={(e) => setComments(e.target.value)}
                                className="col-span-3"
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button 
                            variant="outline" 
                            onClick={() => setActionDialog({ ...actionDialog, open: false })}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button 
                            variant={actionDialog.type === 'approve' ? 'default' : 'destructive'} 
                            onClick={handleAction}
                            disabled={processing || (actionDialog.type === 'reject' && comments.trim() === '')}
                        >
                            {processing ? 'Processing...' : actionDialog.type === 'approve' ? 'Confirm Approval' : 'Confirm Rejection'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </AppLayout>
    );
}
