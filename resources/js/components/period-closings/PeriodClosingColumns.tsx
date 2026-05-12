'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { type PeriodClosing } from '@/types/period-closing';
import {
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
    type CustomTableMeta,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';
import {
    Eye,
    Lock,
    LockOpen,
    MoreHorizontal,
    Pencil,
    Trash,
} from 'lucide-react';

function getStatusVariant(status: PeriodClosing['status']) {
    return status === 'closed' ? 'default' : 'secondary';
}

function getClosingTypeLabel(type: PeriodClosing['closing_type']) {
    return type === 'monthly' ? 'Monthly' : 'Yearly';
}

export const periodClosingColumns: ColumnDef<PeriodClosing>[] = [
    createSelectColumn<PeriodClosing>(),
    {
        accessorKey: 'fiscal_year',
        ...createSortingHeader('Fiscal Year'),
        cell: ({ row }) => row.original.fiscal_year?.name || '-',
    },
    {
        accessorKey: 'period_month',
        ...createSortingHeader('Period Month'),
    },
    {
        accessorKey: 'period_year',
        ...createSortingHeader('Period Year'),
    },
    {
        accessorKey: 'closing_type',
        ...createSortingHeader('Closing Type'),
        cell: ({ row }) => getClosingTypeLabel(row.getValue('closing_type')),
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            return (
                <Badge
                    variant={getStatusVariant(
                        status as PeriodClosing['status'],
                    )}
                >
                    {status === 'draft' ? 'Draft' : 'Closed'}
                </Badge>
            );
        },
    },
    createRowCurrencyAmountColumn<PeriodClosing & { currency?: string | null }>({
        accessorKey: 'net_income',
        label: 'Net Income',
    }),
    {
        accessorKey: 'closed_at',
        ...createSortingHeader('Closed At'),
        cell: ({ row }) => {
            const date = row.getValue('closed_at') as string | null;
            return date ? formatDateByRegionalSettings(date) : '-';
        },
    },
    {
        id: 'row-actions',
        cell: ({ row, table }) => {
            const meta = table.options.meta as CustomTableMeta<PeriodClosing>;
            const isDraft = row.original.status === 'draft';
            const isClosed = row.original.status === 'closed';

            return (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">Open menu</span>
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem
                            onClick={() => meta?.onView?.(row.original)}
                        >
                            <Eye className="mr-2 h-4 w-4" />
                            View
                        </DropdownMenuItem>
                        {isDraft && (
                            <>
                                <DropdownMenuItem
                                    onClick={() => meta?.onEdit?.(row.original)}
                                >
                                    <Pencil className="mr-2 h-4 w-4" />
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => {
                                        const customAction =
                                            meta?.onCustomAction as
                                                | ((
                                                      action: string,
                                                      item: PeriodClosing,
                                                  ) => void)
                                                | undefined;
                                        if (customAction) {
                                            customAction('close', row.original);
                                        }
                                    }}
                                >
                                    <Lock className="mr-2 h-4 w-4" />
                                    Close Period
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() =>
                                        meta?.onDelete?.(row.original)
                                    }
                                    className="text-red-600"
                                >
                                    <Trash className="mr-2 h-4 w-4" />
                                    Delete
                                </DropdownMenuItem>
                            </>
                        )}
                        {isClosed && (
                            <DropdownMenuItem
                                onClick={() => {
                                    const customAction =
                                        meta?.onCustomAction as
                                            | ((
                                                  action: string,
                                                  item: PeriodClosing,
                                              ) => void)
                                            | undefined;
                                    if (customAction) {
                                        customAction('reopen', row.original);
                                    }
                                }}
                            >
                                <LockOpen className="mr-2 h-4 w-4" />
                                Reopen Period
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
