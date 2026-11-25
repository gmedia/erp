'use client';

import { BudgetRowActions } from '@/components/budgets/BudgetRowActions';
import { LabelText } from '@/components/common/TranslatedText';
import { Badge } from '@/components/ui/badge';
import { formatStatusLabel } from '@/lib/status-badge';
import { type Budget } from '@/types/budget';
import {
    type CustomTableMeta,
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
        label: { key: 'column.name', fallback: 'Name' },
        enableSorting: true,
    }),
    {
        accessorKey: 'fiscal_year',
        header: () => (
            <LabelText
                label={{ key: 'column.fiscal_year', fallback: 'Fiscal Year' }}
            />
        ),
        cell: ({ row }) => row.original.fiscal_year?.name ?? '-',
    },
    {
        accessorKey: 'budget_type',
        ...createSortingHeader({
            key: 'column.budget_type',
            fallback: 'Budget Type',
        }),
        cell: ({ row }) => (
            <Badge variant="secondary">
                {formatStatusLabel(row.getValue('budget_type'))}
            </Badge>
        ),
    },
    createStatusBadgeColumn<Budget>({
        accessorKey: 'status',
        label: { key: 'column.status', fallback: 'Status' },
    }),
    createRowCurrencyAmountColumn<Budget & { currency?: string | null }>({
        accessorKey: 'total_amount',
        label: { key: 'column.total_amount', fallback: 'Total Amount' },
    }),
    {
        accessorKey: 'created_at',
        ...createSortingHeader({
            key: 'column.created_at',
            fallback: 'Created At',
        }),
        cell: ({ row }) =>
            formatDateByRegionalSettings(row.getValue('created_at') as string),
    },
    {
        id: 'actions_custom',
        enableHiding: false,
        size: 56,
        cell: ({ row, table }) => {
            const meta = table.options.meta as
                | CustomTableMeta<Budget>
                | undefined;
            return (
                <BudgetRowActions
                    item={row.original}
                    onView={meta?.onView}
                    onEdit={meta?.onEdit}
                    onDelete={meta?.onDelete}
                />
            );
        },
    },
];
