'use client';

import { Badge } from '@/components/ui/badge';
import { formatStatusLabel } from '@/lib/status-badge';
import { type Budget } from '@/types/budget';
import {
    createActionsColumn,
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
    createTextColumn,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';

export const budgetColumns: ColumnDef<Budget>[] = [
    createSelectColumn<Budget>(),
    createTextColumn<Budget>({
        accessorKey: 'name',
        label: 'Name',
        enableSorting: true,
    }),
    {
        accessorKey: 'fiscal_year',
        header: 'Fiscal Year',
        cell: ({ row }) => row.original.fiscal_year?.name ?? '-',
    },
    {
        accessorKey: 'budget_type',
        ...createSortingHeader('Budget Type'),
        cell: ({ row }) => (
            <Badge variant="secondary">
                {formatStatusLabel(row.getValue('budget_type'))}
            </Badge>
        ),
    },
    createStatusBadgeColumn<Budget>({
        accessorKey: 'status',
        label: 'Status',
    }),
    createRowCurrencyAmountColumn<Budget & { currency?: string | null }>({
        accessorKey: 'total_amount',
        label: 'Total Amount',
    }),
    {
        accessorKey: 'created_at',
        ...createSortingHeader('Created At'),
        cell: ({ row }) =>
            formatDateByRegionalSettings(row.getValue('created_at') as string),
    },
    createActionsColumn<Budget>(),
];
