'use client';

import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import { useAssetDepreciationRuns } from '@/hooks/useAssetDepreciationRuns';
import { AssetDepreciationRun } from '@/types/asset-depreciation-run';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { DataTablePagination } from '@/components/common/DataTablePagination';
import {
    Loader2,
    Calculator,
    CheckCircle2,
    Eye,
    Plus,
} from 'lucide-react';
import { format } from 'date-fns';
import { useState } from 'react';
import { CalculateFormModal } from '@/components/asset-depreciation-runs/CalculateFormModal';
import { RunLinesModal } from '@/components/asset-depreciation-runs/RunLinesModal';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Asset Management', href: '/assets' },
    { title: 'Depreciation Runs', href: '/asset-depreciation-runs' },
];

function getStatusBadgeVariant(status: AssetDepreciationRun['status']) {
    switch (status) {
        case 'calculated': return 'default';
        case 'posted': return 'secondary';
        case 'void': return 'destructive';
        default: return 'outline';
    }
}

export default function Index() {
    const {
        data,
        meta,
        isLoading,
        pagination,
        isCalculating,
        isPosting,
        setPage,
        setPerPage,
        calculateDepreciation,
        postToJournal,
    } = useAssetDepreciationRuns();

    const [isCalcOpen, setIsCalcOpen] = useState(false);
    const [viewRunId, setViewRunId] = useState<number | null>(null);

    const from =
        meta.from ??
        (meta.total === 0 ? 0 : (meta.current_page - 1) * meta.per_page + 1);
    const to =
        meta.to ??
        (meta.total === 0 ? 0 : (meta.current_page - 1) * meta.per_page + data.length);

    const paginationView = {
        page: meta.current_page,
        per_page: meta.per_page,
        total: meta.total,
        last_page: meta.last_page,
        from,
        to,
    };

    const handleCalculateSubmit = async (formData: any) => {
        const result = await calculateDepreciation(formData);
        if (result === true) {
            setIsCalcOpen(false);
        }
        return result;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Asset Depreciation Runs" />

            <div className="flex flex-col gap-6 p-6">
                <Card>
                    <CardHeader className="gap-4 sm:flex-row sm:items-start sm:justify-between sm:space-y-0">
                        <div>
                            <CardTitle className="text-2xl font-semibold tracking-tight">
                                Depreciation Runs
                            </CardTitle>
                            <CardDescription className="mt-1">
                                Calculate and review asset depreciation. Once verified, post the calculated amounts to the general ledger.
                            </CardDescription>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button onClick={() => setIsCalcOpen(true)}>
                                <Calculator className="mr-2 h-4 w-4" />
                                Run Calculation
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-4">
                        <div className="overflow-hidden rounded-md border">
                            <Table>
                                <TableHeader className="bg-muted">
                                    <TableRow>
                                        <TableHead>Fiscal Year</TableHead>
                                        <TableHead>Period</TableHead>
                                        <TableHead>Lines</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Journal</TableHead>
                                        <TableHead>Created By</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    {isLoading ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="h-56 text-center">
                                                <div className="flex flex-col items-center justify-center gap-2">
                                                    <Loader2 className="h-8 w-8 animate-spin text-primary" />
                                                    <p className="text-muted-foreground">Loading runs...</p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="h-56 text-center">
                                                <div className="flex flex-col items-center justify-center gap-2 opacity-70">
                                                    <Calculator className="h-10 w-10 text-muted-foreground" />
                                                    <p className="text-lg font-medium">No runs found</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        Start by running a new calculation.
                                                    </p>
                                                    <Button variant="outline" className="mt-4" onClick={() => setIsCalcOpen(true)}>
                                                        <Plus className="mr-2 h-4 w-4" /> Calculate Now
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        data.map((item) => (
                                            <TableRow key={item.id} className="hover:bg-muted/50">
                                                <TableCell className="font-medium">
                                                    {item.fiscal_year?.name || '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-col gap-1">
                                                        <span className="text-sm">
                                                            {format(new Date(item.period_start), 'dd MMM yyyy')}
                                                        </span>
                                                        <span className="text-xs text-muted-foreground">
                                                            to {format(new Date(item.period_end), 'dd MMM yyyy')}
                                                        </span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">{item.lines_count || 0} Assets</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={getStatusBadgeVariant(item.status)} className="capitalize">
                                                        {item.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {item.journal_entry ? (
                                                        <span className="font-mono text-sm">{item.journal_entry.entry_number}</span>
                                                    ) : '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-sm">{item.created_by_user?.name || '-'}</span>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end items-center gap-2">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            title="View Lines"
                                                            onClick={() => setViewRunId(item.id)}
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </Button>

                                                        {item.status === 'calculated' && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="h-8 px-2"
                                                                disabled={isPosting === item.id}
                                                                onClick={() => postToJournal(item.id)}
                                                            >
                                                                {isPosting === item.id ? (
                                                                    <Loader2 className="h-4 w-4 animate-spin mr-1" />
                                                                ) : (
                                                                    <CheckCircle2 className="h-4 w-4 mr-1 text-green-600" />
                                                                )}
                                                                Post
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <DataTablePagination
                            pagination={paginationView}
                            onPageChange={setPage}
                            onPageSizeChange={setPerPage}
                        />
                    </CardContent>
                </Card>
            </div>

            <CalculateFormModal
                open={isCalcOpen}
                loading={isCalculating}
                onClose={() => setIsCalcOpen(false)}
                onSubmit={handleCalculateSubmit}
            />

            <RunLinesModal
                runId={viewRunId}
                open={viewRunId !== null}
                onClose={() => setViewRunId(null)}
            />
        </AppLayout>
    );
}
