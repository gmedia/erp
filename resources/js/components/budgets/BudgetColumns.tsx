'use client';

import { Badge } from '@/components/ui/badge';
import { type Budget } from '@/types/budget';
import {
    createActionsColumn,
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';

type BudgetBadgeVariant = 'default' | 'secondary' | 'outline' | 'destructive';

function getBudgetTypeBadgeVariant(type: string) {
    const variants: Record<
        string,
        BudgetBadgeVariant
    > = {
        operational: 'default',
        capital: 'secondary',
        project: 'outline',
        revenue: 'default',
    };
    return variants[type] ?? 'secondary';
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

function getBudgetStatusLabel(status: string) {
    const labels: Record<string, string> = {
        draft: 'Draft',
        approved: 'Approved',
        locked: 'Locked',
        cancelled: 'Cancelled',
    };
    return labels[status] ?? status;
}

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
            <Badge
                variant={getBudgetTypeBadgeVariant(row.getValue('budget_type'))}
            >
                {getBudgetTypeLabel(row.getValue('budget_type'))}
            </Badge>
        ),
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: ({ row }) => (
            <Badge variant={getBudgetStatusVariant(row.getValue('status'))}>
                {getBudgetStatusLabel(row.getValue('status'))}
            </Badge>
        ),
    },
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
