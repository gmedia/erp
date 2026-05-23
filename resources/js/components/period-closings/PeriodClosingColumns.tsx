'use client';

import { Badge } from '@/components/ui/badge';
import { type PeriodClosing } from '@/types/period-closing';
import {
    createActionsColumn,
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';

function getStatusVariant(status: PeriodClosing['status']) {
    return status === 'closed' ? 'default' : 'secondary';
}

function getClosingTypeLabel(type: PeriodClosing['closing_type']) {
    return type === 'monthly' ? 'Monthly' : 'Yearly';
}

export const periodClosingColumns: ColumnDef<PeriodClosing>[] = [
    createSelectColumn<PeriodClosing>(),
    {
        accessorKey: 'fiscal_year_id',
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
    createRowCurrencyAmountColumn<PeriodClosing & { currency?: string | null }>(
        {
            accessorKey: 'net_income',
            label: 'Net Income',
        },
    ),
    {
        accessorKey: 'closed_at',
        ...createSortingHeader('Closed At'),
        cell: ({ row }) => {
            const date = row.getValue('closed_at') as string | null;
            return date ? formatDateByRegionalSettings(date) : '-';
        },
    },
    createActionsColumn<PeriodClosing>(),
];
