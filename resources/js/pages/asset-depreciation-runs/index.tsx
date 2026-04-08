'use client';

import { CalculateFormModal } from '@/components/asset-depreciation-runs/CalculateFormModal';
import { RunLinesModal } from '@/components/asset-depreciation-runs/RunLinesModal';
import { DataTablePagination } from '@/components/common/DataTablePagination';
import {
    buildStandalonePaginationView,
    StandaloneTablePage,
    TableEmptyStateRow,
    TableLoadingRow,
} from '@/components/common/StandaloneTablePage';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAssetDepreciationRuns } from '@/hooks/useAssetDepreciationRuns';
import { BreadcrumbItem } from '@/types';
import { AssetDepreciationRun } from '@/types/asset-depreciation-run';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type AssetDepreciationCalculationFormData } from '@/utils/schemas';
import { Calculator, CheckCircle2, Eye, Loader2, Plus } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Asset Management', href: '/assets' },
    { title: 'Depreciation Runs', href: '/asset-depreciation-runs' },
];

function getStatusBadgeVariant(status: AssetDepreciationRun['status']) {
    switch (status) {
        case 'calculated':
            return 'default';
        case 'posted':
            return 'secondary';
        case 'void':
            return 'destructive';
        default:
            return 'outline';
    }
}

export default function Index() {
    const {
        data,
        meta,
        isLoading,
        isCalculating,
        isPosting,
        setPage,
        setPerPage,
        calculateDepreciation,
        postToJournal,
    } = useAssetDepreciationRuns();

    const [isCalcOpen, setIsCalcOpen] = useState(false);
    const [viewRunId, setViewRunId] = useState<number | null>(null);

    const paginationView = buildStandalonePaginationView(meta, data.length);

    let tableBodyContent: React.ReactNode;
    if (isLoading) {
        tableBodyContent = (
            <TableLoadingRow
                colSpan={7}
                icon={<Loader2 className="h-8 w-8 animate-spin text-primary" />}
                message="Loading runs..."
            />
        );
    } else if (data.length === 0) {
        tableBodyContent = (
            <TableEmptyStateRow
                colSpan={7}
                icon={<Calculator className="h-10 w-10 text-muted-foreground" />}
                title="No runs found"
                description="Start by running a new calculation."
                action={
                    <Button
                        variant="outline"
                        className="mt-4"
                        onClick={() => setIsCalcOpen(true)}
                    >
                        <Plus className="mr-2 h-4 w-4" /> Calculate Now
                    </Button>
                }
            />
        );
    } else {
        tableBodyContent = data.map((item) => (
            <TableRow key={item.id} className="hover:bg-muted/50">
                <TableCell className="font-medium">
                    {item.fiscal_year?.name || '-'}
                </TableCell>
                <TableCell>
                    <div className="flex flex-col gap-1">
                        <span className="text-sm">
                            {formatDateByRegionalSettings(item.period_start)}
                        </span>
                        <span className="text-xs text-muted-foreground">
                            to {formatDateByRegionalSettings(item.period_end)}
                        </span>
                    </div>
                </TableCell>
                <TableCell>
                    <Badge variant="outline">
                        {item.lines_count || 0} Assets
                    </Badge>
                </TableCell>
                <TableCell>
                    <Badge
                        variant={getStatusBadgeVariant(item.status)}
                        className="capitalize"
                    >
                        {item.status}
                    </Badge>
                </TableCell>
                <TableCell>
                    {item.journal_entry ? (
                        <span className="font-mono text-sm">
                            {item.journal_entry.entry_number}
                        </span>
                    ) : (
                        '-'
                    )}
                </TableCell>
                <TableCell>
                    <span className="text-sm">
                        {item.created_by_user?.name || '-'}
                    </span>
                </TableCell>
                <TableCell className="text-right">
                    <div className="flex items-center justify-end gap-2">
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
                                    <Loader2 className="mr-1 h-4 w-4 animate-spin" />
                                ) : (
                                    <CheckCircle2 className="mr-1 h-4 w-4 text-green-600" />
                                )}
                                Post
                            </Button>
                        )}
                    </div>
                </TableCell>
            </TableRow>
        ));
    }

    const handleCalculateSubmit = async (
        formData: AssetDepreciationCalculationFormData,
    ): Promise<{ success: boolean; errors?: Record<string, string[]> }> => {
        const result = await calculateDepreciation({
            ...formData,
            fiscal_year_id: Number.parseInt(formData.fiscal_year_id, 10),
        });

        if (result === true) {
            setIsCalcOpen(false);
            return { success: true };
        }

        if (typeof result === 'object' && result.errors) {
            return { success: false, errors: result.errors };
        }

        return { success: false };
    };

    return (
        <StandaloneTablePage
            title="Asset Depreciation Runs"
            heading="Depreciation Runs"
            breadcrumbs={breadcrumbs}
            description={
                <>
                    Calculate and review asset depreciation. Once verified,
                    post the calculated amounts to the general ledger.
                </>
            }
            actions={
                <Button onClick={() => setIsCalcOpen(true)}>
                    <Calculator className="mr-2 h-4 w-4" />
                    Run Calculation
                </Button>
            }
        >
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
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
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
        </StandaloneTablePage>
    );
}
