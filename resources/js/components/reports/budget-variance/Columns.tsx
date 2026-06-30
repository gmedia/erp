'use client';

import { Badge } from '@/components/ui/badge';
import { type BudgetVarianceItem } from '@/types/budget';
import { createSortingHeader, createTextColumn } from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { type ColumnDef } from '@tanstack/react-table';

function getVarianceStatusVariant(
    status: BudgetVarianceItem['status'],
): 'default' | 'secondary' | 'destructive' {
    if (status === 'within_budget') return 'default';
    if (status === 'warning') return 'secondary';
    return 'destructive';
}

function getVarianceStatusLabel(status: BudgetVarianceItem['status']) {
    const labels: Record<BudgetVarianceItem['status'], string> = {
        within_budget: 'Within Budget',
        warning: 'Warning',
        over_budget: 'Over Budget',
    };
    return labels[status];
}

export const budgetVarianceColumns: ColumnDef<BudgetVarianceItem>[] = [
    createTextColumn<BudgetVarianceItem>({
        accessorKey: 'account_code',
        label: 'Account Code',
        enableSorting: true,
    }),
    createTextColumn<BudgetVarianceItem>({
        accessorKey: 'account_name',
        label: 'Account Name',
        enableSorting: true,
    }),
    createTextColumn<BudgetVarianceItem>({
        accessorKey: 'account_type',
        label: 'Type',
        enableSorting: false,
    }),
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
    {
        accessorKey: 'allocated',
        ...createSortingHeader('Allocated'),
        cell: ({ row }) =>
            formatCurrencyByRegionalSettings(
                row.getValue('allocated') as number,
                {
                    locale: 'id-ID',
                    currency: 'IDR',
                },
            ),
    },
    {
        accessorKey: 'actual',
        ...createSortingHeader('Actual'),
        cell: ({ row }) =>
            formatCurrencyByRegionalSettings(row.getValue('actual') as number, {
                locale: 'id-ID',
                currency: 'IDR',
            }),
    },
    {
        accessorKey: 'available',
        ...createSortingHeader('Available'),
        cell: ({ row }) =>
            formatCurrencyByRegionalSettings(
                row.getValue('available') as number,
                {
                    locale: 'id-ID',
                    currency: 'IDR',
                },
            ),
    },
    {
        accessorKey: 'variance_percent',
        ...createSortingHeader('Variance %'),
        cell: ({ row }) => {
            const val = row.getValue('variance_percent') as number | null;
            return val === null ? '-' : `${val.toFixed(2)}%`;
        },
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            const status = row.getValue(
                'status',
            ) as BudgetVarianceItem['status'];
            return (
                <Badge variant={getVarianceStatusVariant(status)}>
                    {getVarianceStatusLabel(status)}
                </Badge>
            );
        },
    },
];
