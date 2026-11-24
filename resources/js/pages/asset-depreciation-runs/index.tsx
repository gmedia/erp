'use client';

import { CalculateFormModal } from '@/components/asset-depreciation-runs/CalculateFormModal';
import { RunLinesModal } from '@/components/asset-depreciation-runs/RunLinesModal';
import { GenericActions } from '@/components/common/ActionsDropdown';
import { DataTablePagination } from '@/components/common/DataTablePagination';
import {
    buildStandalonePaginationView,
    StandaloneTablePage,
    TableEmptyStateRow,
    TableLoadingRow,
} from '@/components/common/StandaloneTablePage';
import {
    type StatusActionConfig,
    StatusActionDialog,
} from '@/components/common/StatusActionDialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAssetDepreciationRuns } from '@/hooks/useAssetDepreciationRuns';
import { usePermissions } from '@/hooks/usePermissions';
import { statusBadgeVariant } from '@/lib/status-badge';
import { BreadcrumbItem } from '@/types';
import { AssetDepreciationRun } from '@/types/asset-depreciation-run';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type AssetDepreciationCalculationFormData } from '@/utils/schemas';
import { Ban, Calculator, CheckCircle2, Loader2, Plus } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Asset Management', href: '/assets' },
    { title: 'Depreciation Runs', href: '/asset-depreciation-runs' },
];

export default function Index() {
    const {
        data,
        meta,
        isLoading,
        isCalculating,
        isPosting,
        isVoiding,
        setPage,
        setPerPage,
        calculateDepreciation,
        postToJournal,
        voidRun,
    } = useAssetDepreciationRuns();

    const [isCalcOpen, setIsCalcOpen] = useState(false);
    const [viewRunId, setViewRunId] = useState<number | null>(null);
    const [voidTarget, setVoidTarget] = useState<AssetDepreciationRun | null>(
        null,
    );

    const voidConfig: StatusActionConfig<'void'> | undefined = voidTarget
        ? {
              action: 'void',
              label: 'Void',
              title: 'Void depreciation run?',
              description:
                  'The run is cancelled and does not count toward depreciation. You can calculate the same period again.',
              confirmLabel: 'Void run',
          }
        : undefined;

    const { can } = usePermissions();
    const canEdit = can('asset_depreciation_run.edit');

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
                icon={
                    <Calculator className="h-10 w-10 text-muted-foreground" />
                }
                title="No runs found"
                description="Start by running a new calculation."
                action={
                    canEdit ? (
                        <Button
                            variant="outline"
                            className="mt-4"
                            onClick={() => setIsCalcOpen(true)}
                        >
                            <Plus className="mr-2 h-4 w-4" /> Calculate Now
                        </Button>
                    ) : undefined
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
                <TableCell className="whitespace-nowrap">
                    <Badge
                        variant={statusBadgeVariant(item.status)}
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
                <TableCell className="w-[60px] bg-background text-right md:sticky md:right-0 md:z-20">
                    <div className="flex justify-end">
                        <GenericActions
                            item={item}
                            onView={() => setViewRunId(item.id)}
                            extraItems={
                                item.status === 'calculated' && canEdit
                                    ? [
                                          <DropdownMenuItem
                                              key="post"
                                              disabled={isPosting === item.id}
                                              onClick={() =>
                                                  postToJournal(item.id)
                                              }
                                          >
                                              {isPosting === item.id ? (
                                                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                              ) : (
                                                  <CheckCircle2 className="mr-2 h-4 w-4 text-success" />
                                              )}
                                              Post
                                          </DropdownMenuItem>,
                                          <DropdownMenuItem
                                              key="void"
                                              disabled={isVoiding === item.id}
                                              onClick={() =>
                                                  setVoidTarget(item)
                                              }
                                          >
                                              {isVoiding === item.id ? (
                                                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                              ) : (
                                                  <Ban className="mr-2 h-4 w-4 text-destructive" />
                                              )}
                                              Void
                                          </DropdownMenuItem>,
                                      ]
                                    : undefined
                            }
                        />
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
                    Calculate and review asset depreciation. Once verified, post
                    the calculated amounts to the general ledger.
                </>
            }
            actions={
                canEdit ? (
                    <Button onClick={() => setIsCalcOpen(true)}>
                        <Calculator className="mr-2 h-4 w-4" />
                        Run Calculation
                    </Button>
                ) : undefined
            }
        >
            <Table>
                <TableHeader className="[&_th]:text-muted-foreground">
                    <TableRow>
                        <TableHead>Fiscal Year</TableHead>
                        <TableHead>Period</TableHead>
                        <TableHead>Lines</TableHead>
                        <TableHead className="whitespace-nowrap">
                            Status
                        </TableHead>
                        <TableHead>Journal</TableHead>
                        <TableHead>Created By</TableHead>
                        <TableHead className="w-[60px] bg-background text-right md:sticky md:right-0 md:z-20">
                            <span className="sr-only">Actions</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>{tableBodyContent}</TableBody>
            </Table>

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

            <StatusActionDialog
                config={voidConfig}
                processing={isVoiding !== null}
                processingLabel="Voiding..."
                onConfirm={async () => {
                    if (!voidTarget) return;
                    const ok = await voidRun(voidTarget.id);
                    if (ok) setVoidTarget(null);
                }}
                onCancel={() => setVoidTarget(null)}
            />
        </StandaloneTablePage>
    );
}
