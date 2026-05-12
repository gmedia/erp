'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { type BankReconciliation } from '@/types/bank-reconciliation';
import {
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
    type CustomTableMeta,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';
import { CheckCircle, Eye, MoreHorizontal, Pencil, Trash } from 'lucide-react';

function getStatusVariant(status: BankReconciliation['status']) {
    return status === 'completed' ? 'default' : 'secondary';
}

export const bankReconciliationColumns: ColumnDef<BankReconciliation>[] = [
    createSelectColumn<BankReconciliation>(),
    {
        accessorKey: 'account_name',
        ...createSortingHeader('Account'),
        cell: ({ row }) => {
            const code = row.original.account_code;
            const name = row.getValue('account_name') as string;
            return (
                <div>
                    <div>{code}</div>
                    <div className="text-sm text-gray-500">{name}</div>
                </div>
            );
        },
    },
    {
        accessorKey: 'period_start',
        ...createSortingHeader('Period Start'),
        cell: ({ row }) =>
            formatDateByRegionalSettings(
                row.getValue('period_start') as string,
            ),
    },
    {
        accessorKey: 'period_end',
        ...createSortingHeader('Period End'),
        cell: ({ row }) =>
            formatDateByRegionalSettings(row.getValue('period_end') as string),
    },
    createRowCurrencyAmountColumn<
        BankReconciliation & { currency?: string | null }
    >({
        accessorKey: 'statement_balance',
        label: 'Statement Balance',
    }),
    createRowCurrencyAmountColumn<
        BankReconciliation & { currency?: string | null }
    >({
        accessorKey: 'book_balance',
        label: 'Book Balance',
    }),
    createRowCurrencyAmountColumn<
        BankReconciliation & { currency?: string | null }
    >({
        accessorKey: 'difference',
        label: 'Difference',
    }),
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            return (
                <Badge
                    variant={getStatusVariant(
                        status as BankReconciliation['status'],
                    )}
                >
                    {status === 'in_progress' ? 'In Progress' : 'Completed'}
                </Badge>
            );
        },
    },
    {
        id: 'row-actions',
        cell: ({ row, table }) => {
            const meta = table.options
                .meta as CustomTableMeta<BankReconciliation>;
            const canComplete =
                row.original.status === 'in_progress' &&
                row.original.difference === 0;

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
                        {row.original.status === 'in_progress' && (
                            <>
                                <DropdownMenuItem
                                    onClick={() => meta?.onEdit?.(row.original)}
                                >
                                    <Pencil className="mr-2 h-4 w-4" />
                                    Edit
                                </DropdownMenuItem>
                                {canComplete && (
                                    <DropdownMenuItem
                                        onClick={() => {
                                            const customAction =
                                                meta?.onCustomAction as
                                                    | ((
                                                          action: string,
                                                          item: BankReconciliation,
                                                      ) => void)
                                                    | undefined;
                                            if (customAction) {
                                                customAction(
                                                    'complete',
                                                    row.original,
                                                );
                                            }
                                        }}
                                    >
                                        <CheckCircle className="mr-2 h-4 w-4" />
                                        Complete
                                    </DropdownMenuItem>
                                )}
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
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
