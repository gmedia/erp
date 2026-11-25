'use client';

import { Badge } from '@/components/ui/badge';
import { formatStatusLabel, statusBadgeVariant } from '@/lib/status-badge';
import { type BankReconciliation } from '@/types/bank-reconciliation';
import {
    createActionsColumn,
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';

export const bankReconciliationColumns: ColumnDef<BankReconciliation>[] = [
    createSelectColumn<BankReconciliation>(),
    {
        accessorKey: 'account_id',
        ...createSortingHeader({ key: 'column.account', fallback: 'Account' }),
        cell: ({ row }) => {
            const account = row.original.account;
            return (
                <div>
                    <div>{account?.code}</div>
                    <div className="text-sm text-muted-foreground">
                        {account?.name}
                    </div>
                </div>
            );
        },
    },
    {
        accessorKey: 'period_start',
        ...createSortingHeader({
            key: 'column.period_start',
            fallback: 'Period Start',
        }),
        cell: ({ row }) =>
            formatDateByRegionalSettings(
                row.getValue('period_start') as string,
            ),
    },
    {
        accessorKey: 'period_end',
        ...createSortingHeader({
            key: 'column.period_end',
            fallback: 'Period End',
        }),
        cell: ({ row }) =>
            formatDateByRegionalSettings(row.getValue('period_end') as string),
    },
    createRowCurrencyAmountColumn<
        BankReconciliation & { currency?: string | null }
    >({
        accessorKey: 'statement_balance',
        label: {
            key: 'column.statement_balance',
            fallback: 'Statement Balance',
        },
    }),
    createRowCurrencyAmountColumn<
        BankReconciliation & { currency?: string | null }
    >({
        accessorKey: 'book_balance',
        label: { key: 'column.book_balance', fallback: 'Book Balance' },
    }),
    createRowCurrencyAmountColumn<
        BankReconciliation & { currency?: string | null }
    >({
        accessorKey: 'difference',
        label: { key: 'column.difference', fallback: 'Difference' },
    }),
    {
        accessorKey: 'status',
        ...createSortingHeader({ key: 'column.status', fallback: 'Status' }),
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            return (
                <Badge variant={statusBadgeVariant(status)}>
                    {formatStatusLabel(status)}
                </Badge>
            );
        },
    },
    createActionsColumn<BankReconciliation>(),
];
