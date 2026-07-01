import { memo, useCallback, useEffect, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import axios from '@/lib/axios';
import {
    type BankReconciliation,
    type BankReconciliationItem,
    type UnmatchedJournalLine,
} from '@/types/bank-reconciliation';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { useQueryClient } from '@tanstack/react-query';
import rawAxios from 'axios';
import {
    BookOpen,
    CheckCircle2,
    Link2,
    Link2Off,
    Loader2,
    Search,
    Sparkles,
    X,
} from 'lucide-react';
import { toast } from 'sonner';

interface BankReconciliationWorkspaceProps {
    bankReconciliation: BankReconciliation;
    open: boolean;
    onClose: () => void;
}

interface AutoMatchResult {
    matched: number;
    unmatched: number;
}

interface AccountOption {
    id: number;
    code: string;
    name: string;
}

const currencyOpts = { locale: 'id-ID', currency: 'IDR' } as const;

function useDebouncedValue(value: string, delay: number): string {
    const [debounced, setDebounced] = useState(value);
    useEffect(() => {
        const timer = setTimeout(() => setDebounced(value), delay);
        return () => clearTimeout(timer);
    }, [value, delay]);
    return debounced;
}

export const BankReconciliationWorkspace =
    memo<BankReconciliationWorkspaceProps>(
        function BankReconciliationWorkspace({
            bankReconciliation,
            open,
            onClose,
        }) {
            const queryClient = useQueryClient();

            const [items, setItems] = useState<BankReconciliationItem[]>(
                bankReconciliation.items ?? [],
            );

            const [matchingItemId, setMatchingItemId] = useState<number | null>(
                null,
            );
            const [matchDialogOpen, setMatchDialogOpen] = useState(false);

            const [jeSearch, setJeSearch] = useState('');
            const debouncedSearch = useDebouncedValue(jeSearch, 300);
            const [jeLines, setJeLines] = useState<UnmatchedJournalLine[]>([]);
            const [jeLoading, setJeLoading] = useState(false);

            const [loadingItemId, setLoadingItemId] = useState<number | null>(
                null,
            );
            const [autoMatchLoading, setAutoMatchLoading] = useState(false);
            const [completeLoading, setCompleteLoading] = useState(false);

            const [currentDifference, setCurrentDifference] = useState<number>(
                bankReconciliation.difference,
            );

            const [assigningItemId, setAssigningItemId] = useState<
                number | null
            >(null);
            const [assignDialogOpen, setAssignDialogOpen] = useState(false);
            const [accountSearch, setAccountSearch] = useState('');
            const debouncedAccountSearch = useDebouncedValue(
                accountSearch,
                300,
            );
            const [accountOptions, setAccountOptions] = useState<
                AccountOption[]
            >([]);
            const [accountLoading, setAccountLoading] = useState(false);

            useEffect(() => {
                setItems(bankReconciliation.items ?? []);
            }, [bankReconciliation.items]);

            useEffect(() => {
                if (!open) return;

                let cancelled = false;
                axios
                    .get<{ data: BankReconciliation }>(
                        `/api/bank-reconciliations/${bankReconciliation.id}`,
                    )
                    .then((res) => {
                        if (cancelled) return;
                        setItems(res.data.data.items ?? []);
                        setCurrentDifference(res.data.data.difference);
                    })
                    .catch(() => {
                        if (!cancelled)
                            toast.error('Failed to load reconciliation items.');
                    });

                return () => {
                    cancelled = true;
                };
            }, [open, bankReconciliation.id]);

            useEffect(() => {
                if (!matchDialogOpen) return;

                let cancelled = false;
                setJeLoading(true);

                axios
                    .get<{ data: UnmatchedJournalLine[] }>(
                        `/api/bank-reconciliations/${bankReconciliation.id}/unmatched-journal-lines`,
                        { params: { search: debouncedSearch } },
                    )
                    .then((res) => {
                        if (!cancelled) setJeLines(res.data.data);
                    })
                    .catch(() => {
                        if (!cancelled)
                            toast.error('Failed to load journal entry lines.');
                    })
                    .finally(() => {
                        if (!cancelled) setJeLoading(false);
                    });

                return () => {
                    cancelled = true;
                };
            }, [matchDialogOpen, debouncedSearch, bankReconciliation.id]);

            useEffect(() => {
                if (!assignDialogOpen) return;

                let cancelled = false;
                setAccountLoading(true);

                axios
                    .get<{ data: AccountOption[] }>('/api/accounts', {
                        params: { search: debouncedAccountSearch },
                    })
                    .then((res) => {
                        if (!cancelled) setAccountOptions(res.data.data);
                    })
                    .catch(() => {
                        if (!cancelled) toast.error('Failed to load accounts.');
                    })
                    .finally(() => {
                        if (!cancelled) setAccountLoading(false);
                    });

                return () => {
                    cancelled = true;
                };
            }, [assignDialogOpen, debouncedAccountSearch]);

            const invalidate = useCallback(async () => {
                await queryClient.invalidateQueries({
                    queryKey: ['bank-reconciliations'],
                });
            }, [queryClient]);

            const handleAutoMatch = async () => {
                setAutoMatchLoading(true);
                try {
                    const res = await axios.post<AutoMatchResult>(
                        `/api/bank-reconciliations/${bankReconciliation.id}/auto-match`,
                    );
                    const { matched, unmatched } = res.data;
                    toast.success('Auto Match Complete', {
                        description: `Matched: ${matched}, Unmatched: ${unmatched}`,
                    });
                    await invalidate();
                    const fresh = await axios.get<{ data: BankReconciliation }>(
                        `/api/bank-reconciliations/${bankReconciliation.id}`,
                    );
                    setItems(fresh.data.data.items);
                    setCurrentDifference(fresh.data.data.difference);
                } catch {
                    toast.error('Auto match failed. Please try again.');
                } finally {
                    setAutoMatchLoading(false);
                }
            };

            const openMatchDialog = (itemId: number) => {
                setMatchingItemId(itemId);
                setJeSearch('');
                setJeLines([]);
                setMatchDialogOpen(true);
            };

            const closeMatchDialog = () => {
                setMatchDialogOpen(false);
                setMatchingItemId(null);
            };

            const openAssignDialog = (itemId: number) => {
                setAssigningItemId(itemId);
                setAccountSearch('');
                setAccountOptions([]);
                setAssignDialogOpen(true);
            };

            const closeAssignDialog = () => {
                setAssignDialogOpen(false);
                setAssigningItemId(null);
            };

            const handleAssignAccount = async (account: AccountOption) => {
                if (assigningItemId === null) return;
                setLoadingItemId(assigningItemId);
                try {
                    const res = await axios.put<{
                        data: BankReconciliationItem;
                    }>(
                        `/api/bank-reconciliations/${bankReconciliation.id}/items/${assigningItemId}/assign-account`,
                        { account_id: account.id },
                    );
                    setItems((prev) =>
                        prev.map((it) =>
                            it.id === assigningItemId ? res.data.data : it,
                        ),
                    );
                    toast.success(
                        `Account assigned: ${account.code} - ${account.name}`,
                    );
                    closeAssignDialog();
                    await invalidate();
                    const freshAssign = await axios.get<{
                        data: BankReconciliation;
                    }>(`/api/bank-reconciliations/${bankReconciliation.id}`);
                    setCurrentDifference(freshAssign.data.data.difference);
                } catch (err: unknown) {
                    const msg =
                        rawAxios.isAxiosError(err) &&
                        err.response?.data?.message
                            ? (err.response.data.message as string)
                            : 'Failed to assign account.';
                    toast.error(msg);
                } finally {
                    setLoadingItemId(null);
                }
            };

            const handleMatch = async (jeLineId: number) => {
                if (matchingItemId === null) return;
                setLoadingItemId(matchingItemId);
                try {
                    const res = await axios.post<{
                        data: BankReconciliationItem;
                    }>(
                        `/api/bank-reconciliations/${bankReconciliation.id}/items/${matchingItemId}/match`,
                        { journal_entry_line_id: jeLineId },
                    );
                    setItems((prev) =>
                        prev.map((it) =>
                            it.id === matchingItemId ? res.data.data : it,
                        ),
                    );
                    toast.success('Item matched successfully.');
                    closeMatchDialog();
                    await invalidate();
                    const freshMatch = await axios.get<{
                        data: BankReconciliation;
                    }>(`/api/bank-reconciliations/${bankReconciliation.id}`);
                    setCurrentDifference(freshMatch.data.data.difference);
                } catch (err: unknown) {
                    const msg =
                        rawAxios.isAxiosError(err) &&
                        err.response?.data?.message
                            ? (err.response.data.message as string)
                            : 'Failed to match item.';
                    toast.error(msg);
                } finally {
                    setLoadingItemId(null);
                }
            };

            const handleUnmatch = async (itemId: number) => {
                setLoadingItemId(itemId);
                try {
                    const res = await axios.post<{
                        data: BankReconciliationItem;
                    }>(
                        `/api/bank-reconciliations/${bankReconciliation.id}/items/${itemId}/unmatch`,
                    );
                    setItems((prev) =>
                        prev.map((it) =>
                            it.id === itemId ? res.data.data : it,
                        ),
                    );
                    toast.success('Item unmatched.');
                    await invalidate();
                    const freshUnmatch = await axios.get<{
                        data: BankReconciliation;
                    }>(`/api/bank-reconciliations/${bankReconciliation.id}`);
                    setCurrentDifference(freshUnmatch.data.data.difference);
                } catch (err: unknown) {
                    const msg =
                        rawAxios.isAxiosError(err) &&
                        err.response?.data?.message
                            ? (err.response.data.message as string)
                            : 'Failed to unmatch item.';
                    toast.error(msg);
                } finally {
                    setLoadingItemId(null);
                }
            };

            const handleComplete = async () => {
                setCompleteLoading(true);
                try {
                    await axios.post<{ data: BankReconciliation }>(
                        `/api/bank-reconciliations/${bankReconciliation.id}/complete`,
                    );
                    toast.success('Reconciliation Completed', {
                        description:
                            'Bank reconciliation has been completed and journal entries posted.',
                    });
                    await invalidate();
                    onClose();
                } catch (err: unknown) {
                    const msg =
                        rawAxios.isAxiosError(err) &&
                        err.response?.data?.message
                            ? (err.response.data.message as string)
                            : 'Failed to complete reconciliation.';
                    toast.error('Complete Failed', { description: msg });
                } finally {
                    setCompleteLoading(false);
                }
            };

            const totalItems = items.length;
            const matchedCount = items.filter((i) => i.is_reconciled).length;
            const unmatchedCount = totalItems - matchedCount;
            const unmatchedItems = items.filter((i) => !i.is_reconciled);
            const accountsAssignedCount = unmatchedItems.filter(
                (i) => i.account_id,
            ).length;

            return (
                <>
                    <Dialog open={open} onOpenChange={(v) => !v && onClose()}>
                        <DialogContent className="flex max-h-[90vh] flex-col sm:max-w-6xl">
                            <DialogHeader className="shrink-0">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <DialogTitle className="text-lg font-semibold">
                                            Reconciliation Workspace
                                        </DialogTitle>
                                        <p className="mt-0.5 text-sm text-muted-foreground">
                                            {bankReconciliation.account?.code} —{' '}
                                            {bankReconciliation.account?.name}
                                            {' · '}
                                            {formatDateByRegionalSettings(
                                                bankReconciliation.period_start,
                                            )}{' '}
                                            to{' '}
                                            {formatDateByRegionalSettings(
                                                bankReconciliation.period_end,
                                            )}
                                        </p>
                                    </div>

                                    <div className="flex shrink-0 items-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={handleAutoMatch}
                                            disabled={autoMatchLoading}
                                        >
                                            {autoMatchLoading ? (
                                                <Loader2 className="mr-2 size-4 animate-spin" />
                                            ) : (
                                                <Sparkles className="mr-2 size-4" />
                                            )}
                                            Auto Match
                                        </Button>
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <span>
                                                        <Button
                                                            variant="default"
                                                            size="sm"
                                                            onClick={
                                                                handleComplete
                                                            }
                                                            disabled={
                                                                completeLoading ||
                                                                Math.abs(
                                                                    currentDifference,
                                                                ) > 0.01
                                                            }
                                                        >
                                                            {completeLoading ? (
                                                                <Loader2 className="mr-2 size-4 animate-spin" />
                                                            ) : (
                                                                <CheckCircle2 className="mr-2 size-4" />
                                                            )}
                                                            Complete
                                                        </Button>
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    {Math.abs(
                                                        currentDifference,
                                                    ) > 0.01
                                                        ? 'Difference must be zero to complete'
                                                        : 'Complete reconciliation'}
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={onClose}
                                        >
                                            <X className="size-4" />
                                        </Button>
                                    </div>
                                </div>

                                <div className="mt-3 flex flex-wrap gap-4 rounded-md border bg-muted/40 px-4 py-3 text-sm">
                                    <div className="flex items-center gap-1.5">
                                        <span className="text-muted-foreground">
                                            Total:
                                        </span>
                                        <span className="font-medium">
                                            {totalItems}
                                        </span>
                                    </div>
                                    <Separator
                                        orientation="vertical"
                                        className="h-4"
                                    />
                                    <div className="flex items-center gap-1.5">
                                        <CheckCircle2 className="size-3.5 text-green-600" />
                                        <span className="text-muted-foreground">
                                            Matched:
                                        </span>
                                        <span className="font-medium text-green-700">
                                            {matchedCount}
                                        </span>
                                    </div>
                                    <Separator
                                        orientation="vertical"
                                        className="h-4"
                                    />
                                    <div className="flex items-center gap-1.5">
                                        <span className="text-muted-foreground">
                                            Unmatched:
                                        </span>
                                        <span className="font-medium text-amber-700">
                                            {unmatchedCount}
                                        </span>
                                    </div>
                                    <Separator
                                        orientation="vertical"
                                        className="h-4"
                                    />
                                    <div className="flex items-center gap-1.5">
                                        <BookOpen className="size-3.5 text-blue-600" />
                                        <span className="text-muted-foreground">
                                            Accounts Assigned:
                                        </span>
                                        <span className="font-medium text-blue-700">
                                            {accountsAssignedCount}/
                                            {unmatchedItems.length}
                                        </span>
                                    </div>
                                    <Separator
                                        orientation="vertical"
                                        className="h-4"
                                    />
                                    <div className="flex items-center gap-1.5">
                                        <span className="text-muted-foreground">
                                            Difference:
                                        </span>
                                        <span
                                            className={
                                                Math.abs(currentDifference) <=
                                                0.01
                                                    ? 'font-medium text-green-700'
                                                    : 'font-medium text-red-700'
                                            }
                                        >
                                            {formatCurrencyByRegionalSettings(
                                                currentDifference,
                                                currencyOpts,
                                            )}
                                        </span>
                                    </div>
                                </div>
                            </DialogHeader>

                            <ScrollArea className="min-h-0 flex-1">
                                <div className="min-w-[640px] pr-4">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-[110px]">
                                                    Date
                                                </TableHead>
                                                <TableHead>
                                                    Description
                                                </TableHead>
                                                <TableHead className="w-[110px] text-right">
                                                    Debit
                                                </TableHead>
                                                <TableHead className="w-[110px] text-right">
                                                    Credit
                                                </TableHead>
                                                <TableHead className="w-[80px]">
                                                    Type
                                                </TableHead>
                                                <TableHead className="w-[120px]">
                                                    Status
                                                </TableHead>
                                                <TableHead className="w-[150px]">
                                                    Account
                                                </TableHead>
                                                <TableHead className="w-[180px]">
                                                    Journal Entry
                                                </TableHead>
                                                <TableHead className="w-[100px] text-right">
                                                    Action
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {items.length === 0 ? (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={9}
                                                        className="py-10 text-center text-muted-foreground"
                                                    >
                                                        No bank statement items.
                                                        Import a statement
                                                        first.
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                items.map((item) => (
                                                    <TableRow
                                                        key={item.id}
                                                        className={
                                                            item.is_reconciled
                                                                ? 'bg-green-50/50'
                                                                : undefined
                                                        }
                                                    >
                                                        <TableCell className="text-sm">
                                                            {formatDateByRegionalSettings(
                                                                item.transaction_date,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="max-w-[200px] truncate text-sm">
                                                            {item.description}
                                                        </TableCell>
                                                        <TableCell className="text-right text-sm">
                                                            {item.debit > 0
                                                                ? formatCurrencyByRegionalSettings(
                                                                      item.debit,
                                                                      currencyOpts,
                                                                  )
                                                                : '—'}
                                                        </TableCell>
                                                        <TableCell className="text-right text-sm">
                                                            {item.credit > 0
                                                                ? formatCurrencyByRegionalSettings(
                                                                      item.credit,
                                                                      currencyOpts,
                                                                  )
                                                                : '—'}
                                                        </TableCell>
                                                        <TableCell className="text-sm">
                                                            {item.type}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant={
                                                                    item.is_reconciled
                                                                        ? 'default'
                                                                        : 'secondary'
                                                                }
                                                                className={
                                                                    item.is_reconciled
                                                                        ? 'bg-green-100 text-green-800 hover:bg-green-100'
                                                                        : undefined
                                                                }
                                                            >
                                                                {item.is_reconciled
                                                                    ? 'Matched'
                                                                    : 'Unmatched'}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="text-sm text-muted-foreground">
                                                            {item.journal_entry_number ??
                                                                '—'}
                                                        </TableCell>
                                                        <TableCell>
                                                            {(() => {
                                                                if (
                                                                    item.is_reconciled
                                                                ) {
                                                                    return (
                                                                        <span className="text-xs text-muted-foreground">
                                                                            —
                                                                        </span>
                                                                    );
                                                                }
                                                                if (
                                                                    item.account
                                                                ) {
                                                                    return (
                                                                        <TooltipProvider>
                                                                            <Tooltip>
                                                                                <TooltipTrigger
                                                                                    asChild
                                                                                >
                                                                                    <button
                                                                                        className="max-w-[130px] truncate text-left text-xs font-medium text-blue-700 hover:underline"
                                                                                        onClick={() =>
                                                                                            openAssignDialog(
                                                                                                item.id!,
                                                                                            )
                                                                                        }
                                                                                    >
                                                                                        {
                                                                                            item
                                                                                                .account
                                                                                                .code
                                                                                        }
                                                                                    </button>
                                                                                </TooltipTrigger>
                                                                                <TooltipContent>
                                                                                    {
                                                                                        item
                                                                                            .account
                                                                                            .code
                                                                                    }{' '}
                                                                                    —{' '}
                                                                                    {
                                                                                        item
                                                                                            .account
                                                                                            .name
                                                                                    }
                                                                                </TooltipContent>
                                                                            </Tooltip>
                                                                        </TooltipProvider>
                                                                    );
                                                                }
                                                                return (
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        className="h-7 px-2 text-xs text-muted-foreground hover:text-foreground"
                                                                        onClick={() =>
                                                                            openAssignDialog(
                                                                                item.id!,
                                                                            )
                                                                        }
                                                                    >
                                                                        <BookOpen className="mr-1 size-3" />
                                                                        Assign
                                                                    </Button>
                                                                );
                                                            })()}
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            {loadingItemId ===
                                                            item.id ? (
                                                                <Loader2 className="ml-auto size-4 animate-spin text-muted-foreground" />
                                                            ) : item.is_reconciled ? (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    className="h-7 px-2 text-xs text-red-600 hover:bg-red-50 hover:text-red-700"
                                                                    onClick={() =>
                                                                        handleUnmatch(
                                                                            item.id!,
                                                                        )
                                                                    }
                                                                >
                                                                    <Link2Off className="mr-1 size-3" />
                                                                    Unmatch
                                                                </Button>
                                                            ) : (
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="h-7 px-2 text-xs"
                                                                    onClick={() =>
                                                                        openMatchDialog(
                                                                            item.id!,
                                                                        )
                                                                    }
                                                                >
                                                                    <Link2 className="mr-1 size-3" />
                                                                    Match
                                                                </Button>
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </ScrollArea>
                        </DialogContent>
                    </Dialog>

                    <Dialog
                        open={matchDialogOpen}
                        onOpenChange={closeMatchDialog}
                    >
                        <DialogContent className="flex max-h-[80vh] flex-col sm:max-w-3xl">
                            <DialogHeader className="shrink-0">
                                <DialogTitle>
                                    Select Journal Entry Line
                                </DialogTitle>
                                <p className="text-sm text-muted-foreground">
                                    Choose an unmatched journal entry line to
                                    link to this bank item.
                                </p>
                            </DialogHeader>

                            <div className="relative shrink-0">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Search by entry number, description, reference..."
                                    value={jeSearch}
                                    onChange={(e) =>
                                        setJeSearch(e.target.value)
                                    }
                                    className="pl-9"
                                />
                            </div>

                            <ScrollArea className="min-h-0 flex-1">
                                <div className="min-w-[560px] pr-4">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-[130px]">
                                                    Entry No.
                                                </TableHead>
                                                <TableHead className="w-[100px]">
                                                    Date
                                                </TableHead>
                                                <TableHead className="w-[100px]">
                                                    Reference
                                                </TableHead>
                                                <TableHead>
                                                    Description
                                                </TableHead>
                                                <TableHead className="w-[110px] text-right">
                                                    Debit
                                                </TableHead>
                                                <TableHead className="w-[110px] text-right">
                                                    Credit
                                                </TableHead>
                                                <TableHead className="w-[80px]" />
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {jeLines.length === 0 && jeLoading && (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={7}
                                                        className="py-10 text-center"
                                                    >
                                                        <Loader2 className="mx-auto size-5 animate-spin text-muted-foreground" />
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                            {jeLines.length === 0 && !jeLoading && (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={7}
                                                        className="py-10 text-center text-muted-foreground"
                                                    >
                                                        No unmatched journal
                                                        entry lines found.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                            {jeLines.length > 0 && (
                                                jeLines.map((line) => (
                                                    <TableRow key={line.id}>
                                                        <TableCell className="font-mono text-xs">
                                                            {
                                                                line
                                                                    .journal_entry
                                                                    .entry_number
                                                            }
                                                        </TableCell>
                                                        <TableCell className="text-sm">
                                                            {formatDateByRegionalSettings(
                                                                line
                                                                    .journal_entry
                                                                    .entry_date,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-sm text-muted-foreground">
                                                            {line.journal_entry
                                                                .reference ??
                                                                '—'}
                                                        </TableCell>
                                                        <TableCell className="max-w-[180px] truncate text-sm">
                                                            {line.memo ??
                                                                line
                                                                    .journal_entry
                                                                    .description}
                                                        </TableCell>
                                                        <TableCell className="text-right text-sm">
                                                            {line.debit > 0
                                                                ? formatCurrencyByRegionalSettings(
                                                                      line.debit,
                                                                      currencyOpts,
                                                                  )
                                                                : '—'}
                                                        </TableCell>
                                                        <TableCell className="text-right text-sm">
                                                            {line.credit > 0
                                                                ? formatCurrencyByRegionalSettings(
                                                                      line.credit,
                                                                      currencyOpts,
                                                                  )
                                                                : '—'}
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="h-7 px-2 text-xs"
                                                                disabled={
                                                                    loadingItemId !==
                                                                    null
                                                                }
                                                                onClick={() =>
                                                                    handleMatch(
                                                                        line.id,
                                                                    )
                                                                }
                                                            >
                                                                Select
                                                            </Button>
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </ScrollArea>

                            <div className="flex shrink-0 justify-end pt-2">
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={closeMatchDialog}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </DialogContent>
                    </Dialog>

                    <Dialog
                        open={assignDialogOpen}
                        onOpenChange={closeAssignDialog}
                    >
                        <DialogContent className="flex max-h-[70vh] flex-col sm:max-w-lg">
                            <DialogHeader className="shrink-0">
                                <DialogTitle>Assign GL Account</DialogTitle>
                                <p className="text-sm text-muted-foreground">
                                    Select an account to assign to this
                                    unmatched bank item (e.g. bank charges,
                                    interest).
                                </p>
                            </DialogHeader>

                            <div className="relative shrink-0">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Search by account code or name..."
                                    value={accountSearch}
                                    onChange={(e) =>
                                        setAccountSearch(e.target.value)
                                    }
                                    className="pl-9"
                                />
                            </div>

                            <ScrollArea className="min-h-0 flex-1">
                                <div className="pr-4">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-[100px]">
                                                    Code
                                                </TableHead>
                                                <TableHead>Name</TableHead>
                                                <TableHead className="w-[70px]" />
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {(() => {
                                                if (accountLoading) {
                                                    return (
                                                        <TableRow>
                                                            <TableCell
                                                                colSpan={3}
                                                                className="py-10 text-center"
                                                            >
                                                                <Loader2 className="mx-auto size-5 animate-spin text-muted-foreground" />
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                }
                                                if (
                                                    accountOptions.length === 0
                                                ) {
                                                    return (
                                                        <TableRow>
                                                            <TableCell
                                                                colSpan={3}
                                                                className="py-10 text-center text-muted-foreground"
                                                            >
                                                                No accounts
                                                                found.
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                }
                                                return accountOptions.map(
                                                    (account) => (
                                                        <TableRow
                                                            key={account.id}
                                                        >
                                                            <TableCell className="font-mono text-xs">
                                                                {account.code}
                                                            </TableCell>
                                                            <TableCell className="text-sm">
                                                                {account.name}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="h-7 px-2 text-xs"
                                                                    disabled={
                                                                        loadingItemId !==
                                                                        null
                                                                    }
                                                                    onClick={() =>
                                                                        handleAssignAccount(
                                                                            account,
                                                                        )
                                                                    }
                                                                >
                                                                    Select
                                                                </Button>
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                );
                                            })()}
                                        </TableBody>
                                    </Table>
                                </div>
                            </ScrollArea>

                            <div className="flex shrink-0 justify-end pt-2">
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={closeAssignDialog}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </DialogContent>
                    </Dialog>
                </>
            );
        },
    );
