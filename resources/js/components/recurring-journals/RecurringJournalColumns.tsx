'use client';

import { Badge } from '@/components/ui/badge';
import { statusBadgeVariant } from '@/lib/status-badge';
import { type RecurringJournal } from '@/types/recurring-journal';
import {
    createActionsColumn,
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';

function getFrequencyLabel(frequency: RecurringJournal['frequency']) {
    const labels = {
        daily: 'Daily',
        weekly: 'Weekly',
        monthly: 'Monthly',
        quarterly: 'Quarterly',
        annual: 'Yearly',
    };
    return labels[frequency] || frequency;
}

export const recurringJournalColumns: ColumnDef<RecurringJournal>[] = [
    createSelectColumn<RecurringJournal>(),
    createTextColumn<RecurringJournal>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        enableSorting: true,
    }),
    {
        accessorKey: 'frequency',
        ...createSortingHeader({
            key: 'column.frequency',
            fallback: 'Frequency',
        }),
        cell: ({ row }) => getFrequencyLabel(row.getValue('frequency')),
    },
    {
        accessorKey: 'next_run_date',
        ...createSortingHeader({
            key: 'column.next_run_date',
            fallback: 'Next Run Date',
        }),
        cell: ({ row }) =>
            formatDateByRegionalSettings(
                row.getValue('next_run_date') as string,
            ),
    },
    createRowCurrencyAmountColumn<
        RecurringJournal & { currency?: string | null }
    >({
        accessorKey: 'total_amount',
        label: { key: 'column.total_amount', fallback: 'Total Amount' },
    }),
    {
        accessorKey: 'auto_post',
        ...createSortingHeader({
            key: 'column.auto_post',
            fallback: 'Auto Post',
        }),
        cell: ({ row }) => (
            <Badge
                variant={row.getValue('auto_post') ? 'default' : 'secondary'}
            >
                {row.getValue('auto_post') ? 'Yes' : 'No'}
            </Badge>
        ),
    },
    {
        accessorKey: 'is_active',
        ...createSortingHeader({
            key: 'column.is_active',
            fallback: 'Is Active',
        }),
        cell: ({ row }) => (
            <Badge
                variant={statusBadgeVariant(
                    row.getValue('is_active') ? 'active' : 'inactive',
                )}
            >
                {row.getValue('is_active') ? 'Active' : 'Inactive'}
            </Badge>
        ),
    },
    {
        accessorKey: 'created_at',
        ...createSortingHeader({
            key: 'column.created_at',
            fallback: 'Created At',
        }),
        cell: ({ row }) =>
            formatDateByRegionalSettings(row.getValue('created_at') as string),
    },
    createActionsColumn<RecurringJournal>(),
];
