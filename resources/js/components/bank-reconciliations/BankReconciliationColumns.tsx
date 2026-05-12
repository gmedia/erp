'use client';

import { Badge } from '@/components/ui/badge';
import { type BankReconciliation } from '@/types/bank-reconciliation';
import {
    createActionsColumn,
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';

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
    createActionsColumn<BankReconciliation>(),
];
