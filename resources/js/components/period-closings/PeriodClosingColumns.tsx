'use client';

import { PeriodClosingRowActions } from '@/components/period-closings/PeriodClosingRowActions';
import { Badge } from '@/components/ui/badge';
import { formatStatusLabel, statusBadgeVariant } from '@/lib/status-badge';
import { type PeriodClosing } from '@/types/period-closing';
import {
    type CustomTableMeta,
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';

function formatMonthName(value: unknown): string {
    const month = Number(value);
    if (!Number.isInteger(month) || month < 1 || month > 12) {
        return '-';
    }

    return formatDateByRegionalSettings(new Date(2000, month - 1, 1), {
        dateFormat: 'F',
        locale: 'en-US',
    });
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
        cell: ({ row }) => (
            <span>{formatMonthName(row.getValue('period_month'))}</span>
        ),
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
                <Badge variant={statusBadgeVariant(status)}>
                    {formatStatusLabel(status)}
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
    {
        id: 'actions_custom',
        enableHiding: false,
        size: 56,
        cell: ({ row, table }) => {
            const meta = table.options.meta as
                | CustomTableMeta<PeriodClosing>
                | undefined;
            return (
                <PeriodClosingRowActions
                    item={row.original}
                    onView={meta?.onView}
                    onEdit={meta?.onEdit}
                    onDelete={meta?.onDelete}
                />
            );
        },
    },
];
