import { memo } from 'react';

import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { type Budget } from '@/types/budget';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

interface BudgetViewModalProps {
    item: Budget | null;
    open: boolean;
    onClose: () => void;
}

function getBudgetStatusVariant(
    status: string,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    const variants: Record<
        string,
        'default' | 'secondary' | 'outline' | 'destructive'
    > = {
        draft: 'secondary',
        approved: 'default',
        locked: 'outline',
        cancelled: 'destructive',
    };
    return variants[status] ?? 'secondary';
}

function getBudgetTypeLabel(type: string) {
    const labels: Record<string, string> = {
        operational: 'Operational',
        capital: 'Capital',
        project: 'Project',
        revenue: 'Revenue',
    };
    return labels[type] ?? type;
}

export const BudgetViewModal = memo<BudgetViewModalProps>(
    function BudgetViewModal({ item, open, onClose }) {
        if (!item) return null;

        const totalAllocated = (item.lines ?? []).reduce(
            (sum, l) => sum + (Number(l.allocated_amount) || 0),
            0,
        );

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="Budget Details"
                description="View complete details of this budget"
                contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-5xl"
            >
                <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                    <div className="space-y-6 py-2">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="min-w-0">
                                <p className="text-sm font-medium text-gray-500">
                                    Name
                                </p>
                                <p>{item.name}</p>
                            </div>
                            <div className="min-w-0">
                                <p className="text-sm font-medium text-gray-500">
                                    Fiscal Year
                                </p>
                                <p>{item.fiscal_year?.name ?? '-'}</p>
                            </div>
                            <div className="min-w-0">
                                <p className="text-sm font-medium text-gray-500">
                                    Budget Type
                                </p>
                                <p>{getBudgetTypeLabel(item.budget_type)}</p>
                            </div>
                            <div className="min-w-0">
                                <p className="text-sm font-medium text-gray-500">
                                    Status
                                </p>
                                <Badge
                                    variant={getBudgetStatusVariant(
                                        item.status,
                                    )}
                                >
                                    {item.status.charAt(0).toUpperCase() +
                                        item.status.slice(1)}
                                </Badge>
                            </div>
                            <div className="min-w-0">
                                <p className="text-sm font-medium text-gray-500">
                                    Total Amount
                                </p>
                                <p>
                                    {formatCurrencyByRegionalSettings(
                                        item.total_amount,
                                        {
                                            locale: 'id-ID',
                                            currency: 'IDR',
                                        },
                                    )}
                                </p>
                            </div>
                            <div className="min-w-0">
                                <p className="text-sm font-medium text-gray-500">
                                    Created By
                                </p>
                                <p>{item.creator?.name ?? '-'}</p>
                            </div>
                            {item.approver && (
                                <>
                                    <div className="min-w-0">
                                        <p className="text-sm font-medium text-gray-500">
                                            Approved By
                                        </p>
                                        <p>{item.approver.name}</p>
                                    </div>
                                    <div className="min-w-0">
                                        <p className="text-sm font-medium text-gray-500">
                                            Approved At
                                        </p>
                                        <p>
                                            {item.approved_at
                                                ? formatDateByRegionalSettings(
                                                      item.approved_at,
                                                  )
                                                : '-'}
                                        </p>
                                    </div>
                                </>
                            )}
                            {item.description && (
                                <div className="min-w-0 sm:col-span-2">
                                    <p className="text-sm font-medium text-gray-500">
                                        Description
                                    </p>
                                    <p>{item.description}</p>
                                </div>
                            )}
                        </div>

                        {(item.lines ?? []).length > 0 && (
                            <div className="min-w-0 overflow-x-auto rounded-md border">
                                <Table className="min-w-[600px]">
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Account Code</TableHead>
                                            <TableHead>Account Name</TableHead>
                                            <TableHead>Period Start</TableHead>
                                            <TableHead>Period End</TableHead>
                                            <TableHead className="text-right">
                                                Allocated Amount
                                            </TableHead>
                                            <TableHead>Notes</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {(item.lines ?? []).map((line, idx) => (
                                            <TableRow key={line.id ?? idx}>
                                                <TableCell>
                                                    {line.account?.code ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {line.account?.name ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {formatDateByRegionalSettings(
                                                        line.period_start,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatDateByRegionalSettings(
                                                        line.period_end,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrencyByRegionalSettings(
                                                        line.allocated_amount,
                                                        {
                                                            locale: 'id-ID',
                                                            currency: 'IDR',
                                                        },
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {line.notes ?? '-'}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                    <TableFooter>
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="font-bold"
                                            >
                                                Total
                                            </TableCell>
                                            <TableCell className="text-right font-bold">
                                                {formatCurrencyByRegionalSettings(
                                                    totalAllocated,
                                                    {
                                                        locale: 'id-ID',
                                                        currency: 'IDR',
                                                    },
                                                )}
                                            </TableCell>
                                            <TableCell />
                                        </TableRow>
                                    </TableFooter>
                                </Table>
                            </div>
                        )}
                    </div>
                </div>
            </ViewModalShell>
        );
    },
);
