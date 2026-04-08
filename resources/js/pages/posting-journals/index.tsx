'use client';

import { DataTablePagination } from '@/components/common/DataTablePagination';
import {
    buildStandalonePaginationView,
    StandaloneTablePage,
    TableEmptyStateRow,
    TableLoadingRow,
} from '@/components/common/StandaloneTablePage';
import { JournalEntryViewModal } from '@/components/journal-entries/JournalEntryViewModal';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePostingJournal } from '@/hooks/usePostingJournal';
import { BreadcrumbItem } from '@/types';
import { JournalEntry } from '@/types/journal-entry';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import {
    AlertCircle,
    CheckCircle2,
    Eye,
    Loader2,
    Search,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Posting Journals',
        href: '/posting-journals',
    },
];

function getStatusBadgeVariant(status: JournalEntry['status']) {
    if (status === 'posted') return 'default';
    if (status === 'void') return 'destructive';
    return 'secondary';
}

export default function Index() {
    const {
        data,
        meta,
        isLoading,
        selectedIds,
        toggleSelection,
        selectAll,
        clearSelection,
        postSelected,
        isPosting,
        handleSearch,
        searchQuery,
        setPage,
        setPerPage,
    } = usePostingJournal();

    const [viewItem, setViewItem] = useState<JournalEntry | null>(null);
    const [viewOpen, setViewOpen] = useState(false);

    const allSelected = data.length > 0 && selectedIds.length === data.length;

    const pageTotals = useMemo(() => {
        const totals = data.reduce(
            (acc, item) => {
                acc.debit += item.total_debit;
                acc.credit += item.total_credit;
                return acc;
            },
            { debit: 0, credit: 0 },
        );

        const selectedSet = new Set(selectedIds);
        const selectedTotals = data.reduce(
            (acc, item) => {
                if (!selectedSet.has(item.id)) return acc;
                acc.debit += item.total_debit;
                acc.credit += item.total_credit;
                return acc;
            },
            { debit: 0, credit: 0 },
        );

        return { totals, selectedTotals };
    }, [data, selectedIds]);

    const paginationView = buildStandalonePaginationView(meta, data.length);
    const { from, to } = paginationView;

    let tableBodyContent: React.ReactNode;
    if (isLoading) {
        tableBodyContent = (
            <TableLoadingRow
                colSpan={7}
                icon={<Loader2 className="h-8 w-8 animate-spin text-primary" />}
                message="Loading journals..."
            />
        );
    } else if (data.length === 0) {
        tableBodyContent = (
            <TableEmptyStateRow
                colSpan={7}
                icon={
                    <AlertCircle className="h-10 w-10 text-muted-foreground" />
                }
                title="No draft journals found"
                description="All journal entries are already posted or voided."
            />
        );
    } else {
        tableBodyContent = data.map((item) => {
            const preview = item.lines
                .slice(0, 2)
                .map((l) => l.account_name || l.account_code || 'Line')
                .filter(Boolean)
                .join(' • ');

            return (
                <TableRow key={item.id} className="hover:bg-muted/50">
                    <TableCell className="pt-4 align-top">
                        <Checkbox
                            checked={selectedIds.includes(item.id)}
                            onCheckedChange={() => toggleSelection(item.id)}
                            aria-label={`Select ${item.entry_number}`}
                        />
                    </TableCell>

                    <TableCell>
                        <div className="flex flex-col gap-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="font-mono font-semibold text-primary">
                                    {item.entry_number}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    {formatDateByRegionalSettings(
                                        item.entry_date,
                                    )}
                                </span>
                            </div>
                            <div className="line-clamp-2 text-sm text-muted-foreground">
                                {item.description}
                            </div>
                        </div>
                    </TableCell>

                    <TableCell className="pt-4 align-top">
                        <div className="flex flex-col gap-1">
                            <div className="text-sm font-medium">
                                {formatNumberByRegionalSettings(
                                    item.lines.length,
                                )}{' '}
                                line(s)
                            </div>
                            {preview.length > 0 && (
                                <div className="line-clamp-2 text-xs text-muted-foreground">
                                    {preview}
                                </div>
                            )}
                        </div>
                    </TableCell>

                    <TableCell className="pt-4 text-right align-top">
                        {formatCurrencyByRegionalSettings(item.total_debit, {
                            locale: 'id-ID',
                            currency: 'IDR',
                        })}
                    </TableCell>
                    <TableCell className="pt-4 text-right align-top">
                        {formatCurrencyByRegionalSettings(item.total_credit, {
                            locale: 'id-ID',
                            currency: 'IDR',
                        })}
                    </TableCell>

                    <TableCell className="pt-4 text-center align-top">
                        <Badge
                            variant={getStatusBadgeVariant(item.status)}
                            className="capitalize"
                        >
                            {item.status}
                        </Badge>
                    </TableCell>

                    <TableCell className="pt-3 text-right align-top">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => {
                                setViewItem(item);
                                setViewOpen(true);
                            }}
                            aria-label={`View ${item.entry_number}`}
                        >
                            <Eye className="h-4 w-4" />
                        </Button>
                    </TableCell>
                </TableRow>
            );
        });
    }

    return (
        <StandaloneTablePage
            title="Posting Journals"
            breadcrumbs={breadcrumbs}
            description={
                <>
                    Review draft journal entries and post them to the general
                    ledger.
                </>
            }
            actions={
                <>
                    <div className="relative sm:w-80">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={searchQuery}
                            placeholder="Search journals..."
                            className="pl-9"
                            onChange={(e) => handleSearch(e.target.value)}
                        />
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={clearSelection}
                            disabled={selectedIds.length === 0 || isPosting}
                        >
                            <X className="mr-2 h-4 w-4" />
                            Clear
                        </Button>
                        <Button
                            onClick={postSelected}
                            disabled={selectedIds.length === 0 || isPosting}
                        >
                            {isPosting ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <CheckCircle2 className="mr-2 h-4 w-4" />
                            )}
                            {isPosting
                                ? 'Posting...'
                                : `Post Selected (${selectedIds.length})`}
                        </Button>
                    </div>
                </>
            }
        >
            <div className="flex flex-col gap-3 rounded-md border bg-muted/20 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm">
                    <div>
                        <span className="text-muted-foreground">
                            Draft journals
                        </span>{' '}
                        <span className="font-medium text-foreground">
                            {formatNumberByRegionalSettings(meta.total)}
                        </span>
                    </div>
                    <div>
                        <span className="text-muted-foreground">Showing</span>{' '}
                        <span className="font-medium text-foreground">
                            {formatNumberByRegionalSettings(from)}–
                            {formatNumberByRegionalSettings(to)}
                        </span>
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Page totals
                        </span>{' '}
                        <span className="font-medium text-foreground">
                            {formatCurrencyByRegionalSettings(
                                pageTotals.totals.debit,
                                {
                                    locale: 'id-ID',
                                    currency: 'IDR',
                                },
                            )}{' '}
                            /{' '}
                            {formatCurrencyByRegionalSettings(
                                pageTotals.totals.credit,
                                {
                                    locale: 'id-ID',
                                    currency: 'IDR',
                                },
                            )}
                        </span>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm">
                    <div>
                        <span className="text-muted-foreground">Selected</span>{' '}
                        <span className="font-medium text-foreground">
                            {formatNumberByRegionalSettings(selectedIds.length)}
                        </span>
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Selected totals
                        </span>{' '}
                        <span className="font-medium text-foreground">
                            {formatCurrencyByRegionalSettings(
                                pageTotals.selectedTotals.debit,
                                {
                                    locale: 'id-ID',
                                    currency: 'IDR',
                                },
                            )}{' '}
                            /{' '}
                            {formatCurrencyByRegionalSettings(
                                pageTotals.selectedTotals.credit,
                                {
                                    locale: 'id-ID',
                                    currency: 'IDR',
                                },
                            )}
                        </span>
                    </div>
                </div>
            </div>

            {selectedIds.length > 0 && (
                <Alert>
                    <AlertTitle>Selection active</AlertTitle>
                    <AlertDescription>
                        {formatNumberByRegionalSettings(selectedIds.length)}{' '}
                        draft journal(s) selected in this page.
                    </AlertDescription>
                </Alert>
            )}

            <div className="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader className="bg-muted">
                        <TableRow>
                            <TableHead className="w-[52px]">
                                <Checkbox
                                    checked={allSelected}
                                    onCheckedChange={selectAll}
                                    aria-label="Select all"
                                />
                            </TableHead>
                            <TableHead>Journal</TableHead>
                            <TableHead className="w-[220px]">Lines</TableHead>
                            <TableHead className="text-right">Debit</TableHead>
                            <TableHead className="text-right">Credit</TableHead>
                            <TableHead className="text-center">
                                Status
                            </TableHead>
                            <TableHead className="w-[60px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>{tableBodyContent}</TableBody>
                </Table>
            </div>

            <DataTablePagination
                pagination={paginationView}
                onPageChange={setPage}
                onPageSizeChange={setPerPage}
            />

            <JournalEntryViewModal
                item={viewItem}
                open={viewOpen}
                onClose={() => setViewOpen(false)}
            />
        </StandaloneTablePage>
    );
}
