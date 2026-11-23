import { PageHeader } from '@/components/common/PageHeader';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/contexts/i18n-context';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { cn } from '@/lib/utils';
import { ApprovalRequest, ApprovalRequestStep } from '@/types/approval';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { format, formatDistanceToNow, isValid, parseISO } from 'date-fns';
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

const APPROVABLE_TYPE_LABELS: Record<string, string> = {
    PurchaseOrder: 'Purchase Order',
    PurchaseRequest: 'Purchase Request',
    SupplierBill: 'Supplier Bill',
    CustomerInvoice: 'Customer Invoice',
    ApPayment: 'AP Payment',
    ArReceipt: 'AR Receipt',
    CreditNote: 'Credit Note',
    JournalEntry: 'Journal Entry',
    StockTransfer: 'Stock Transfer',
    StockAdjustment: 'Stock Adjustment',
    GoodsReceipt: 'Goods Receipt',
    SupplierReturn: 'Supplier Return',
    InventoryStocktake: 'Inventory Stocktake',
    Asset: 'Asset',
    AssetMaintenance: 'Asset Maintenance',
    AssetMovement: 'Asset Movement',
    AssetStocktake: 'Asset Stocktake',
    Budget: 'Budget',
    PeriodClosing: 'Period Closing',
    BankReconciliation: 'Bank Reconciliation',
    RecurringJournal: 'Recurring Journal',
};

function shortApprovableType(approvableType: string): string {
    return approvableType.split('\\').pop() ?? approvableType;
}

function formatApprovableTypeLabel(approvableType: string): string {
    const short = shortApprovableType(approvableType);
    if (APPROVABLE_TYPE_LABELS[short]) {
        return APPROVABLE_TYPE_LABELS[short];
    }
    return short.replaceAll(/([a-z])([A-Z])/g, '$1 $2');
}

function documentTitle(step: ApprovalRequestStep): string {
    const approvable = step.request.approvable;
    if (approvable) {
        const name =
            (typeof approvable.name === 'string' && approvable.name.trim()) ||
            (typeof approvable.description === 'string' &&
                approvable.description.trim()) ||
            '';
        const code =
            typeof approvable.asset_code === 'string'
                ? approvable.asset_code.trim()
                : '';
        if (code && name) {
            return `${code} · ${name}`;
        }
        if (name) {
            return name;
        }
        if (code) {
            return code;
        }
    }

    const stepName =
        step.flow_step?.name?.trim() || step.flowStep?.name?.trim();
    if (stepName) {
        return stepName;
    }

    return `${formatApprovableTypeLabel(step.request.approvable_type)} #${step.request.approvable_id}`;
}

function formatSubmittedAt(submittedAt: string | null | undefined): {
    relative: string;
    absolute: string;
} | null {
    if (!submittedAt) {
        return null;
    }
    const date = parseISO(submittedAt);
    if (!isValid(date)) {
        return null;
    }
    return {
        relative: formatDistanceToNow(date, { addSuffix: true }),
        absolute: format(date, 'dd MMM yyyy HH:mm'),
    };
}

function StatusBadge({ status }: Readonly<{ status: string }>) {
    switch (status) {
        case 'pending':
            return (
                <Badge variant="secondary" className="gap-1 font-normal">
                    <Clock className="h-3 w-3" /> Pending
                </Badge>
            );
        case 'approved':
            return (
                <Badge
                    variant="default"
                    className="gap-1 bg-success font-normal hover:bg-success"
                >
                    <Check className="h-3 w-3" /> Approved
                </Badge>
            );
        case 'rejected':
            return (
                <Badge variant="destructive" className="gap-1 font-normal">
                    <X className="h-3 w-3" /> Rejected
                </Badge>
            );
        default:
            return (
                <Badge variant="outline" className="font-normal">
                    {status}
                </Badge>
            );
    }
}

function TabCount({ count }: Readonly<{ count: number }>) {
    return (
        <span
            className={cn(
                'inline-flex min-w-5 shrink-0 items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold tabular-nums',
                'bg-muted text-muted-foreground group-data-[state=active]:bg-primary/15 group-data-[state=active]:text-primary',
            )}
        >
            {count}
        </span>
    );
}

interface AssignmentOption {
    id: number;
    label: string;
}

interface AssignmentOptions {
    drivers: AssignmentOption[];
    units: AssignmentOption[];
}

function RequestNumber({ id }: Readonly<{ id: number | string }>) {
    const { t } = useTranslation();

    return (
        <span className="font-mono text-[11px] text-muted-foreground tabular-nums">
            {t('my_approvals.request_number', { id: String(id) })}
        </span>
    );
}

export default function MyApprovalsPage() {
    const queryClient = useQueryClient();
    const [actionDialog, setActionDialog] = useState<{
        open: boolean;
        type: 'approve' | 'reject' | null;
        requestStep: ApprovalRequestStep | null;
        driverId: string;
        unitId: string;
        reviewNotes: string;
    }>({
        open: false,
        type: null,
        requestStep: null,
        driverId: '',
        unitId: '',
        reviewNotes: '',
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

    const { data: assignmentOptions } = useQuery<AssignmentOptions>({
        queryKey: ['vehicle-loans', 'assignment-options'],
        queryFn: async () => {
            const response = await axios.get<{
                drivers: Array<{ id: number; name: string }>;
                units: Array<{
                    id: number;
                    plate_number: string;
                    vehicle_type: string;
                }>;
            }>('/api/vehicle-loans/assignment-options');
            return {
                drivers: response.data.drivers.map((d) => ({
                    id: d.id,
                    label: d.name,
                })),
                units: response.data.units.map((u) => ({
                    id: u.id,
                    label: `${u.plate_number} (${u.vehicle_type})`,
                })),
            };
        },
        staleTime: 5 * 60 * 1000,
    });

    const pending = data?.pending ?? [];
    const approved = data?.approved ?? [];
    const rejected = data?.rejected ?? [];
    const all = data?.all ?? [];

    const isVehicleLoan = (step: ApprovalRequestStep | null): boolean =>
        shortApprovableType(step?.request.approvable_type ?? '') ===
        'VehicleLoan';

    const openActionDialog = (
        type: 'approve' | 'reject',
        step: ApprovalRequestStep,
    ) => {
        const approvable = step.request.approvable as
            | { driver_id?: number; vehicle_unit_id?: number }
            | undefined;
        setActionDialog({
            open: true,
            type,
            requestStep: step,
            driverId: approvable?.driver_id ? String(approvable.driver_id) : '',
            unitId: approvable?.vehicle_unit_id
                ? String(approvable.vehicle_unit_id)
                : '',
            reviewNotes: '',
        });
        setComments('');
    };

    const handleAction = () => {
        if (!actionDialog.requestStep || !actionDialog.type) return;

        setProcessing(true);
        const approvalId = actionDialog.requestStep.request.id;
        const loanId = actionDialog.requestStep.request.approvable_id;

        const submitReview = async (): Promise<void> => {
            if (!isVehicleLoan(actionDialog.requestStep)) return;
            await axios.post(`/api/vehicle-loans/${loanId}/review-assignment`, {
                driver_id: actionDialog.driverId
                    ? Number(actionDialog.driverId)
                    : null,
                vehicle_unit_id: actionDialog.unitId
                    ? Number(actionDialog.unitId)
                    : null,
                review_notes: actionDialog.reviewNotes || null,
            });
        };

        void submitReview()
            .then(() =>
                axios.post(
                    `/api/my-approvals/${approvalId}/${actionDialog.type}`,
                    { comments },
                ),
            )
            .then(() => {
                setActionDialog({
                    open: false,
                    type: null,
                    requestStep: null,
                    driverId: '',
                    unitId: '',
                    reviewNotes: '',
                });
                void queryClient.invalidateQueries({
                    queryKey: ['my-approvals'],
                });
                toast.success(`Request ${actionDialog.type}d successfully`);
            })
            .catch((err) => {
                toast.error(`Failed to ${actionDialog.type} request`);
                console.error(err);
            })
            .finally(() => {
                setProcessing(false);
            });
    };

    const getDocUrl = (request: ApprovalRequest) => {
        const type = shortApprovableType(request.approvable_type);
        const id = request.approvable_id;
        const ulid = request.approvable?.ulid;

        if (type === 'Asset') {
            return `/assets/${ulid || id}`;
        }
        if (type === 'VehicleLoan') {
            return `/vehicle-loans/${id}`;
        }

        return '#';
    };

    let actionConfirmText = 'Confirm Rejection';
    if (processing) {
        actionConfirmText = 'Processing...';
    } else if (actionDialog.type === 'approve') {
        actionConfirmText = 'Confirm Approval';
    }

    const renderList = (
        items: ApprovalRequestStep[],
        isPendingTab: boolean = false,
    ) => {
        if (!items || items.length === 0) {
            return (
                <div className="flex flex-col items-center justify-center rounded-md border border-dashed bg-muted/15 px-4 py-8 text-center">
                    <Clock className="mb-2 h-8 w-8 text-muted-foreground/40" />
                    <h3 className="text-sm font-medium">No requests found</h3>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        You&apos;re all caught up.
                    </p>
                </div>
            );
        }

        return (
            <div className="space-y-2">
                {items.map((step: ApprovalRequestStep) => {
                    const typeLabel = formatApprovableTypeLabel(
                        step.request.approvable_type,
                    );
                    const title = documentTitle(step);
                    const stepLabel =
                        step.flow_step?.name?.trim() ||
                        step.flowStep?.name?.trim() ||
                        null;
                    const submitted = formatSubmittedAt(
                        step.request.submitted_at,
                    );
                    const showStepUnderTitle =
                        Boolean(stepLabel) && stepLabel !== title;

                    return (
                        <Card
                            key={step.id}
                            className="border-border/80 shadow-none transition-colors hover:bg-muted/20"
                        >
                            <CardContent className="p-3 sm:p-3.5">
                                <div className="flex flex-col gap-2.5">
                                    <div className="flex flex-col items-start justify-between gap-2 sm:flex-row sm:items-start">
                                        <div className="min-w-0 flex-1 space-y-1">
                                            <div className="flex flex-wrap items-center gap-1.5">
                                                <Badge
                                                    variant="outline"
                                                    className="h-5 border-border/70 px-1.5 text-[11px] font-medium tracking-normal text-foreground"
                                                >
                                                    {typeLabel}
                                                </Badge>
                                                <RequestNumber
                                                    id={
                                                        step.request
                                                            .approvable_id
                                                    }
                                                />
                                                <StatusBadge
                                                    status={step.status}
                                                />
                                            </div>
                                            <h4 className="text-sm leading-snug font-semibold tracking-tight text-foreground">
                                                {title}
                                            </h4>
                                            {showStepUnderTitle ? (
                                                <p className="text-xs text-muted-foreground">
                                                    Step: {stepLabel}
                                                </p>
                                            ) : null}
                                            <div className="flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs text-muted-foreground">
                                                <span>
                                                    Submitted by{' '}
                                                    {step.request.submitter
                                                        ?.name || 'Unknown'}
                                                </span>
                                                {submitted ? (
                                                    <>
                                                        <span
                                                            aria-hidden
                                                            className="text-border"
                                                        >
                                                            ·
                                                        </span>
                                                        <time
                                                            dateTime={
                                                                step.request
                                                                    .submitted_at
                                                            }
                                                            title={
                                                                submitted.absolute
                                                            }
                                                        >
                                                            {submitted.relative}
                                                            <span className="text-muted-foreground/70">
                                                                {' '}
                                                                (
                                                                {
                                                                    submitted.absolute
                                                                }
                                                                )
                                                            </span>
                                                        </time>
                                                    </>
                                                ) : null}
                                            </div>
                                        </div>
                                        <div className="w-full sm:w-auto sm:shrink-0">
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="h-8 w-full px-2 sm:w-auto"
                                                asChild
                                            >
                                                <Link
                                                    to={getDocUrl(step.request)}
                                                >
                                                    <Eye className="mr-1.5 h-3.5 w-3.5" />
                                                    View Doc
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>

                                    {isPendingTab && (
                                        <div className="flex w-full flex-wrap items-center gap-1.5 border-t border-border/60 pt-2">
                                            <Button
                                                size="sm"
                                                className="h-8 w-full rounded-full sm:w-auto"
                                                onClick={() =>
                                                    openActionDialog(
                                                        'approve',
                                                        step,
                                                    )
                                                }
                                            >
                                                <Check className="mr-1 h-3.5 w-3.5" />
                                                Approve
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive-soft"
                                                className="h-8 w-full rounded-full sm:w-auto"
                                                onClick={() =>
                                                    openActionDialog(
                                                        'reject',
                                                        step,
                                                    )
                                                }
                                            >
                                                <X className="mr-1 h-3.5 w-3.5" />
                                                Reject
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'My Approvals', href: '/my-approvals' },
            ]}
        >
            <Helmet>
                <title>
                    My Approvals - {import.meta.env.VITE_APP_NAME || 'ERP'}
                </title>
            </Helmet>

            <div className="flex w-full flex-col gap-4 p-4 sm:p-5">
                <PageHeader
                    title="My Approvals"
                    description="Review and act on documents waiting for your decision."
                    meta={
                        !isLoading && !error ? (
                            <span>
                                {pending.length} pending · {approved.length}{' '}
                                approved · {rejected.length} rejected
                            </span>
                        ) : undefined
                    }
                />

                {isLoading && (
                    <div className="flex items-center gap-2 rounded-md border bg-card px-3 py-2.5 text-sm text-muted-foreground">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        Loading approvals...
                    </div>
                )}

                {error && (
                    <div className="rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2.5 text-sm text-destructive">
                        Failed to load approvals. Please refresh and try again.
                    </div>
                )}

                <Tabs defaultValue="pending" className="w-full gap-3">
                    <TabsList className="grid h-9 w-full grid-cols-4 lg:w-[28rem]">
                        <TabsTrigger
                            value="pending"
                            className="group gap-1.5 text-xs sm:text-sm"
                        >
                            Pending
                            <TabCount count={pending.length} />
                        </TabsTrigger>
                        <TabsTrigger
                            value="approved"
                            className="group gap-1.5 text-xs sm:text-sm"
                        >
                            Approved
                            <TabCount count={approved.length} />
                        </TabsTrigger>
                        <TabsTrigger
                            value="rejected"
                            className="group gap-1.5 text-xs sm:text-sm"
                        >
                            Rejected
                            <TabCount count={rejected.length} />
                        </TabsTrigger>
                        <TabsTrigger
                            value="all"
                            className="group gap-1.5 text-xs sm:text-sm"
                        >
                            All
                            <TabCount count={all.length} />
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="pending" className="mt-0">
                        {renderList(pending, true)}
                    </TabsContent>
                    <TabsContent value="approved" className="mt-0">
                        {renderList(approved, false)}
                    </TabsContent>
                    <TabsContent value="rejected" className="mt-0">
                        {renderList(rejected, false)}
                    </TabsContent>
                    <TabsContent value="all" className="mt-0">
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
                        {isVehicleLoan(actionDialog.requestStep) && (
                            <div className="space-y-3 rounded-md border border-border/70 bg-muted/20 p-3">
                                <p className="text-xs font-medium text-muted-foreground">
                                    Review penugasan kendaraan
                                </p>
                                <div className="grid gap-2">
                                    <Label htmlFor="review_driver">
                                        Driver
                                    </Label>
                                    <Select
                                        value={actionDialog.driverId || 'none'}
                                        onValueChange={(value) =>
                                            setActionDialog((current) => ({
                                                ...current,
                                                driverId:
                                                    value === 'none'
                                                        ? ''
                                                        : value,
                                            }))
                                        }
                                    >
                                        <SelectTrigger id="review_driver">
                                            <SelectValue placeholder="Kosong — tidak diubah" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                Kosong — tidak diubah
                                            </SelectItem>
                                            {assignmentOptions?.drivers.map(
                                                (driver) => (
                                                    <SelectItem
                                                        key={driver.id}
                                                        value={String(
                                                            driver.id,
                                                        )}
                                                    >
                                                        {driver.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="review_unit">
                                        Unit kendaraan
                                    </Label>
                                    <Select
                                        value={actionDialog.unitId || 'none'}
                                        onValueChange={(value) =>
                                            setActionDialog((current) => ({
                                                ...current,
                                                unitId:
                                                    value === 'none'
                                                        ? ''
                                                        : value,
                                            }))
                                        }
                                    >
                                        <SelectTrigger id="review_unit">
                                            <SelectValue placeholder="Kosong — tidak diubah" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                Kosong — tidak diubah
                                            </SelectItem>
                                            {assignmentOptions?.units.map(
                                                (unit) => (
                                                    <SelectItem
                                                        key={unit.id}
                                                        value={String(unit.id)}
                                                    >
                                                        {unit.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="review_notes">
                                        Catatan review (opsional)
                                    </Label>
                                    <Textarea
                                        id="review_notes"
                                        value={actionDialog.reviewNotes}
                                        onChange={(e) =>
                                            setActionDialog((current) => ({
                                                ...current,
                                                reviewNotes: e.target.value,
                                            }))
                                        }
                                        rows={2}
                                    />
                                </div>
                            </div>
                        )}
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
                            {actionConfirmText}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
